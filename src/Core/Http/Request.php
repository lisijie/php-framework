<?php
namespace Core\Http;

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

/**
 * HTTP请求
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Http
 */
class Request extends Message implements ServerRequestInterface
{
    /**
     * @var array[]
     */
    protected $filters = [];

    /**
     * @var Uri
     */
    protected $uri;

    /**
     * @var string
     */
    protected $method = 'GET';

    /**
     * @var string
     */
    protected $requestTarget;

    /**
     * @var array
     */
    protected $serverParams = [];

    /**
     * @var array
     */
    protected $queryParams = [];

    /**
     * @var array
     */
    protected $uploadedFiles = [];

    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * @var array
     */
    protected $cookies = [];

    /**
     * @var null|array|object
     */
    protected $parsedBody;

    /**
     * @var array
     */
    protected static $validMethods = [
        'CONNECT' => 1,
        'DELETE' => 1,
        'GET' => 1,
        'HEAD' => 1,
        'OPTIONS' => 1,
        'PATCH' => 1,
        'POST' => 1,
        'PUT' => 1,
        'TRACE' => 1,
    ];

    public function __construct(
        $method,
        $uri,
        Headers $headers = null,
        StreamInterface $body = null,
        $version = '1.1',
        $serverParams = []
    )
    {
        $this->method = $this->filterMethod($method);
        $this->uri = $uri;
        $this->headers = $headers ?: new Headers();
        $this->body = $body;
        $this->protocol = $version;
        $this->serverParams = $serverParams;
    }

    public static function createFromGlobals()
    {
        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
        $uri = self::getUriFromGlobals();
        $headers = Headers::createFromGlobals();
        $body = new Stream(fopen('php://input', 'r'));
        $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? str_replace('HTTP/', '', $_SERVER['SERVER_PROTOCOL']) : '1.1';

        $request = new static($method, $uri, $headers, $body, $protocol, $_SERVER);

        return $request
            ->withCookieParams($_COOKIE)
            ->withQueryParams($_GET)
            ->withParsedBody($_POST)
            ->withUploadedFiles(self::normalizeFiles($_FILES));
    }

    /**
     * 根据环境变量创建URI对象
     *
     * @return UriInterface
     */
    public static function getUriFromGlobals()
    {
        $uri = new Uri('');
        $uri = $uri->withScheme(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http');

        $hasPort = false;
        if (isset($_SERVER['HTTP_HOST'])) {
            $hostHeaderParts = explode(':', $_SERVER['HTTP_HOST']);
            $uri = $uri->withHost($hostHeaderParts[0]);
            if (isset($hostHeaderParts[1])) {
                $hasPort = true;
                $uri = $uri->withPort(intval($hostHeaderParts[1]));
            }
        } elseif (isset($_SERVER['SERVER_NAME'])) {
            $uri = $uri->withHost($_SERVER['SERVER_NAME']);
        } elseif (isset($_SERVER['SERVER_ADDR'])) {
            $uri = $uri->withHost($_SERVER['SERVER_ADDR']);
        }

        if (!$hasPort && isset($_SERVER['SERVER_PORT'])) {
            $uri = $uri->withPort(intval($_SERVER['SERVER_PORT']));
        }

        $hasQuery = false;
        if (isset($_SERVER['REQUEST_URI'])) {
            $requestUriParts = explode('?', $_SERVER['REQUEST_URI']);
            $uri = $uri->withPath($requestUriParts[0]);
            if (isset($requestUriParts[1])) {
                $hasQuery = true;
                $uri = $uri->withQuery($requestUriParts[1]);
            }
        }

        if (!$hasQuery && isset($_SERVER['QUERY_STRING'])) {
            $uri = $uri->withQuery($_SERVER['QUERY_STRING']);
        }

        return $uri;
    }

    /**
     * 将PHP的 $_FILES 转换成 UploadedFile 对象
     *
     * @param array $files
     * @return array
     */
    public static function normalizeFiles(array $files)
    {
        $normalized = [];

        foreach ($files as $key => $value) {
            if ($value instanceof UploadedFileInterface) {
                $normalized[$key] = $value;
            } elseif (is_array($value) && isset($value['tmp_name'])) {
                $normalized[$key] = self::createUploadedFileFromSpec($value);
            } elseif (is_array($value)) {
                $normalized[$key] = self::normalizeFiles($value);
                continue;
            } else {
                throw new InvalidArgumentException('Invalid value in files specification');
            }
        }

        return $normalized;
    }

    private static function createUploadedFileFromSpec(array $value)
    {
        if (is_array($value['tmp_name'])) {
            $normalizedFiles = [];
            foreach (array_keys($value['tmp_name']) as $key) {
                $spec = [
                    'tmp_name' => $value['tmp_name'][$key],
                    'size' => $value['size'][$key],
                    'error' => $value['error'][$key],
                    'name' => $value['name'][$key],
                    'type' => $value['type'][$key],
                ];
                $normalizedFiles[$key] = self::createUploadedFileFromSpec($spec);
            }
            return $normalizedFiles;
        }
        return new UploadedFile(
            $value['tmp_name'],
            $value['name'],
            $value['type'],
            (int)$value['size'],
            (int)$value['error'],
            true
        );
    }

    /**
     * 过滤HTTP方法
     *
     * @param $method
     * @return string
     */
    protected function filterMethod($method)
    {
        $method = strtoupper($method);
        if (!isset(self::$validMethods[$method])) {
            throw new InvalidArgumentException('Invalid http method: ' . $method);
        }
        return $method;
    }

    /**
     * 返回请求目标地址
     *
     * @return string
     */
    public function getRequestTarget()
    {
        if ($this->requestTarget) {
            return $this->requestTarget;
        }
        if ($this->uri == null) {
            return '/';
        }
        $target = $this->uri->getPath();
        if ($query = $this->uri->getQuery()) {
            $target .= '?' . $query;
        }
        $this->requestTarget = $target;
        return $this->requestTarget;
    }

    /**
     * 返回指定请求目标的新实例
     *
     * @link http://tools.ietf.org/html/rfc7230#section-5.3 (for the various
     *     request-target forms allowed in request messages)
     * @param mixed $requestTarget
     * @return static
     */
    public function withRequestTarget($requestTarget)
    {
        if (preg_match('#\s#', $requestTarget)) {
            throw new InvalidArgumentException(
                'Invalid request target provided; cannot contain whitespace'
            );
        }
        $obj = clone $this;
        $obj->requestTarget = $requestTarget;
        return $obj;
    }

    /**
     * 返回请求方法
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * 返回指定http方法的新实例
     *
     * @param string $method HTTP方法名，大小写不敏感
     * @return static
     * @throws InvalidArgumentException 方法无效时
     */
    public function withMethod($method)
    {
        $method = $this->filterMethod($method);
        $obj = clone $this;
        $obj->method = $method;
        return $obj;
    }

    /**
     * 返回请求URI实例
     *
     * @return UriInterface
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * 返回指定URI的新实例
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     * @param UriInterface $uri
     * @param bool $preserveHost 是否保留原主机头
     * @return static
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $obj = clone $this;
        $obj->uri = $uri;

        if (!$preserveHost) {
            if ($uri->getHost() !== '') {
                $obj->headers->set('Host', $uri->getHost());
            }
        } else {
            if ($uri->getHost() !== '' && (!$obj->hasHeader('Host') || $obj->getHeaderLine('Host') === '')) {
                $obj->headers->set('Host', $uri->getHost());
            }
        }
        return $obj;
    }

    /**
     * 返回服务器参数
     *
     * 通常是PHP的 $_SERVER 环境变量。
     *
     * @return array
     */
    public function getServerParams()
    {
        return $this->serverParams;
    }

    /**
     * 返回请求的Cookie信息
     *
     * @return array
     */
    public function getCookieParams()
    {
        return $this->cookies;
    }

    /**
     * 返回包含指定cookies的新实例
     *
     * @param array $cookies
     * @return static
     */
    public function withCookieParams(array $cookies)
    {
        $obj = clone $this;
        $obj->cookies = $cookies;
        return $obj;
    }

    /**
     * 返回解码后的URL查询参数信息
     *
     * @return array
     */
    public function getQueryParams()
    {
        if (is_array($this->queryParams)) {
            return $this->queryParams;
        }
        if ($this->uri === null) {
            return [];
        }
        parse_str($this->uri->getQuery(), $this->queryParams);
        return $this->queryParams;
    }

    /**
     * 返回包含指定查询参数的新实例
     *
     * @param array $query
     * @return static
     */
    public function withQueryParams(array $query)
    {
        $obj = clone $this;
        $obj->queryParams = $query;
        return $obj;
    }

    /**
     * 返回上传的文件信息
     *
     * @return array 树结构的UploadedFileInterface数组
     */
    public function getUploadedFiles()
    {
        return $this->uploadedFiles;
    }

    /**
     * 返回指定了上传文件的新实例
     *
     * @param array $uploadedFiles
     * @return static
     * @throws InvalidArgumentException
     */
    public function withUploadedFiles(array $uploadedFiles)
    {
        $obj = clone $this;
        $obj->uploadedFiles = $uploadedFiles;
        return $obj;
    }

    /**
     * 返回请求体
     *
     * @return null|array|object
     */
    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    /**
     * 返回指定请求体的新实例
     *
     * @param null|array|object $data 反序列化的body数据
     * @return static
     * @throws InvalidArgumentException
     */
    public function withParsedBody($data)
    {
        $obj = clone $this;
        $obj->parsedBody = $data;
        return $obj;
    }

    /**
     * 返回从请求中解析出的属性信息
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * 返回单个属性值
     *
     * @see getAttributes()
     * @param string $name 属性名
     * @param mixed $default 默认值
     * @return mixed
     */
    public function getAttribute($name, $default = null)
    {
        if (false === array_key_exists($name, $this->attributes)) {
            return $default;
        }
        return $this->attributes[$name];
    }

    /**
     * 返回指定属性的新实例
     *
     * @see getAttributes()
     * @param string $name
     * @param mixed $value
     * @return static
     */
    public function withAttribute($name, $value)
    {
        $obj = clone $this;
        $obj->attributes[$name] = $value;
        return $obj;
    }

    /**
     * 返回指定属性的新实例
     *
     * @param array $attributes
     * @return static
     */
    public function withAttributes(array $attributes)
    {
        $obj = clone $this;
        $obj->attributes = $attributes;
        return $obj;
    }

    /**
     * 返回删掉指定属性的新实例
     *
     * @see getAttributes()
     * @param string $name
     * @return static
     */
    public function withoutAttribute($name)
    {
        if (false === array_key_exists($name, $this->attributes)) {
            return $this;
        }
        $obj = clone $this;
        unset($this->attributes[$name]);
        return $obj;
    }

    /**
     * 检查请求方法
     *
     * @param $method
     * @return bool
     */
    public function isMethod($method)
    {
        return strtoupper($method) === $this->getMethod();
    }

    /**
     * 是否GET请求
     *
     * @return bool
     */
    public function isGet()
    {
        return $this->isMethod('GET');
    }

    /**
     * 是否POST请求
     *
     * @return bool
     */
    public function isPost()
    {
        return $this->isMethod('POST');
    }

    /**
     * 是否OPTIONS请求
     *
     * @return bool
     */
    public function isOptions()
    {
        return $this->isMethod('OPTIONS');
    }

    /**
     * 是否DELETE请求
     *
     * @return bool
     */
    public function isDelete()
    {
        return $this->isMethod('DELETE');
    }

    /**
     * 是否HEAD请求
     *
     * @return bool
     */
    public function isHead()
    {
        return $this->isMethod('HEAD');
    }

    /**
     * 是否PUT请求
     *
     * @return bool
     */
    public function isPut()
    {
        return $this->isMethod('PUT');
    }

    /**
     * 是否HTTPS连接
     *
     * @return bool
     */
    public function isHttps()
    {
        return $this->uri->getScheme() === 'https';
    }

    /**
     * 是否AJAX请求
     *
     * @return bool
     */
    public function isAjax()
    {
        return $this->isXHR();
    }

    /**
     * 是否XMLHttpRequest请求
     *
     * @return bool
     */
    public function isXHR()
    {
        return strtolower($this->getHeaderLine('X-Requested-With')) === 'xmlhttprequest';
    }

    /**
     * 返回指定服务器环境变量值
     *
     * @param string $name
     * @param mixed $default
     * @param bool $applyFilter
     * @return mixed
     */
    public function getServerParam($name, $default = null, $applyFilter = true)
    {
        $name = strtoupper($name);
        if (isset($this->serverParams[$name])) {
            return $applyFilter ? $this->applyFilter($this->serverParams[$name]) : $this->serverParams[$name];
        }
        return $default;
    }

    /**
     * 返回指定查询参数值
     *
     * @param string $name
     * @param null|mixed $default
     * @param bool $applyFilter
     * @return mixed|null
     */
    public function getQueryParam($name, $default = null, $applyFilter = true)
    {
        $getParams = $this->getQueryParams();
        if (isset($getParams[$name])) {
            return $applyFilter ? $this->applyFilter($getParams[$name]) : $getParams[$name];
        }
        return $default;
    }

    /**
     * 返回POST参数值
     *
     * @param string $name
     * @param null|mixed $default
     * @param bool $applyFilter
     * @return mixed|null
     */
    public function getPostParam($name, $default = null, $applyFilter = true)
    {
        $postParams = $this->getParsedBody();
        if (isset($postParams[$name])) {
            return $applyFilter ? $this->applyFilter($postParams[$name]) : $postParams[$name];
        }
        return $default;
    }

    /**
     * 返回指定cookie值
     *
     * @param string $name
     * @param null|mixed $default
     * @param bool $applyFilter
     * @return mixed|null
     */
    public function getCookieParam($name, $default = null, $applyFilter = true)
    {
        if (isset($this->cookies[$name])) {
            return $applyFilter ? $this->applyFilter($this->cookies[$name]) : $this->cookies[$name];
        }
        return $default;
    }

    /**
     * 返回指定解密后的cookie值
     *
     * @param string $name
     * @param null|mixed $default
     * @param bool $applyFilter
     * @return mixed|null
     */
    public function getSecureCookieParam($name, $default = null, $applyFilter = true)
    {
        if (isset($this->cookies[$name])) {
            return $applyFilter ? $this->applyFilter($this->cookies[$name]) : $this->cookies[$name];
        }
        return $default;
    }

    /**
     * 返回指定上传文件
     *
     * @param string $name
     * @return UploadedFileInterface|null 存在返回UploadedFileInterface对象，不存在返回null
     */
    public function getUploadedFile($name)
    {
        return isset($this->uploadedFiles[$name]) ? $this->uploadedFiles[$name] : null;
    }

    /**
     * 获取客户端IPv4地址
     *
     * @return string IP地址
     */
    public function getClientIp()
    {
        if (isset($this->serverParams['HTTP_X_FORWARDED_FOR']) && $this->serverParams['HTTP_X_FORWARDED_FOR']) {
            $ip = $this->serverParams['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($this->serverParams['HTTP_CLIENT_IP']) && $this->serverParams['HTTP_CLIENT_IP']) {
            $ip = $this->serverParams['HTTP_CLIENT_IP'];
        } else {
            $ip = isset($this->serverParams['REMOTE_ADDR']) ? $this->serverParams['REMOTE_ADDR'] : '';
        }
        return preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $ip) ? $ip : 'unknown';
    }

    /**
     * 获取不包含域名和入口文件的URL
     *
     * 例如请求URL是 http://www.example.com:8080/app/index.php?r=main/test ，返回结果为 /app
     *
     * @param bool $full 是否返回加上域名的完整URL
     * @return string
     */
    public function getBaseUrl($full = false)
    {
        if ($full) {
            $url = $this->uri->getScheme() . '://' . $this->uri->getAuthority() . $this->getBasePath();
        } else {
            $url = $this->getBasePath();
        }
        return $url;
    }

    /**
     * 获取当前项目的路径
     *
     * 例如请求URL是 http://www.example.com/app/index.php ，返回结果为 /app
     * 请求URL是 http://www.example.com/index.php ，返回结果为 /
     *
     * @return string
     */
    public function getBasePath()
    {
        $scriptName = $this->getServerParam('SCRIPT_NAME', '/');
        return '/' . trim(dirname($scriptName), '\\/');
    }

    /**
     * 增加过滤器
     *
     * @param callable $callback 回调过滤器函数
     * @throws InvalidArgumentException
     */
    public function addFilter($callback)
    {
        if (!is_callable($callback)) {
            throw new InvalidArgumentException('Param invalid');
        }
        $this->filters[] = $callback;
    }

    /**
     * 应用过滤器
     *
     * @param mixed $value 要过滤的值
     * @return mixed 过滤后的值
     */
    protected function applyFilter($value)
    {
        if (count($this->filters)) {
            foreach ($this->filters as $filter) {
                if (is_array($value)) {
                    foreach ($value as $k => $v) {
                        $value[$k] = $this->applyFilter($v);
                    }
                } else {
                    $value = call_user_func($filter, $value);
                }
            }
        }
        return $value;
    }

}