<?php

namespace Core\Http;

/**
 * Request 类
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core
 */
class Request
{
    // 参数过滤器
    protected $filters = array();

    // http头
    protected $headers;

    // cookies
    protected $cookies;

    public function __construct(Headers $header = null, Cookies $cookie = null)
    {
        $this->headers = is_null($header) ? Headers::createFromEnv() : $header;
        $this->cookies = is_null($cookie) ? new Cookies() : $cookie;
    }

    /**
     * 获取用于请求的cookies对象
     *
     * @return Cookies
     */
    public function cookies()
    {
        return $this->cookies;
    }

    /**
     * 获取用于请求的headers对象
     *
     * @return Headers
     */
    public function headers()
    {
        return $this->headers;
    }

    /**
     * 增加请求参数
     *
     * @param array $params 参数列表
     */
    public function addParams(array $params)
    {
        $_GET = array_merge($_GET, $params);
    }

    /**
     * 获取HTTP请求方法
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->getServer('REQUEST_METHOD');
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
     * 获取HTTP请求协议
     *
     * @return string
     */
    public function getScheme()
    {
        if ($this->getServer('SERVER_PORT') == 443) {
            return 'https';
        }
        return 'http';
    }

    /**
     * 获取请求的端口
     *
     * @return string
     */
    public function getPort()
    {
        return $this->getServer('SERVER_PORT');
    }

    /**
     * 获取查询字符串
     *
     * @return string
     */
    public function getQueryString()
    {
        return $this->getServer('QUERY_STRING');
    }

    /**
     * 获取原始请求体
     *
     * @return string
     */
    public function getContent()
    {
        return file_get_contents("php://input");
    }

    /**
     * 获取查询参数
     *
     * @param $name
     * @param $default
     * @return mixed
     */
    public function getQuery($name = null, $default = null)
    {
        if (null === $name) {
            return $this->applyFilter($_GET);
        }
        if (isset($_GET[$name])) {
            return $this->applyFilter($_GET[$name]);
        }
        return $default;
    }

    /**
     * 获取POST参数
     *
     * @param $name
     * @param $default
     * @return mixed
     */
    public function getPost($name = null, $default = null)
    {
        if (null === $name) {
            return $this->applyFilter($_POST);
        }
        if (isset($_POST[$name])) {
            return $this->applyFilter($_POST[$name]);
        }
        return $default;
    }

    /**
     * 获取上传的文件
     *
     * @param $name
     */
    public function getFiles($name)
    {

    }

    /**
     * 获取GET|POST值
     *
     * 优先从GET取，取不到从POST取
     *
     * @param string $name 键名
     * @param mixed $default 默认值
     * @return mixed 值
     */
    public function get($name, $default = null)
    {
        if (null === ($value = $this->getQuery($name))) {
            $value = $this->getPost($name, $default);
        }
        return $value;
    }

    /**
     * 增加过滤器
     *
     * @param $callback 回调过滤器函数
     * @throws \InvalidArgumentException
     */
    public function addFilter($callback)
    {
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('回调函数不可用');
        }
        $this->filters[] = $callback;
    }

    /**
     * 应用过滤器
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

    /**
     * 获取$_SERVER环境变量值
     * @param string $name 键名
     * @param mixed $default 默认值
     * @return mixed 值`
     */
    public function getServer($name, $default = NULL)
    {
        $value = isset($_SERVER[$name]) ? $_SERVER[$name] : $default;
        $value = $this->applyFilter($value);
        return $value;
    }

    /**
     * 获取客户端IP地址
     *
     * @return string IP地址
     */
    public function getClientIp()
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR']) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP']) && $_SERVER['HTTP_CLIENT_IP']) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        }
        return preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $ip) ? $ip : 'unknown';
    }

    /**
     * 获取cookie
     *
     * @param $name
     * @return bool|string
     */
    public function getCookie($name = null)
    {
        if (is_null($name)) {
            return $this->cookies;
        }
        return $this->cookies->get($name);
    }

    /**
     * 获取当前脚本名称
     *
     * @return mixed
     */
    public function getScriptName()
    {
        return $this->getServer('SCRIPT_NAME', $this->getServer('ORIG_SCRIPT_NAME', ''));
    }

    /**
     * 获取访问主机地址
     *
     * @return string
     */
    public function getHost()
    {
        if (!($host = $this->getServer('HTTP_HOST'))) {
            if (!($host = $this->getServer('SERVER_NAME'))) {
                $host = $this->getServer('SERVER_ADDR');
            }
        }
        return $host;
    }

    /**
     * 获取主机URL
     *
     * @return string
     */
    public function getHostUrl()
    {
        $isHttps = $this->isHttps();
        $port = $this->getServer('SERVER_PORT');
        return ($isHttps ? 'https://' : 'http://') . $this->getHost() . ((($isHttps && $port != 443) || (!$isHttps && $port != 80)) ? ':' . $port : '');
    }

    /**
     * 获取基础URL
     *
     * @return string
     */
    public function getBaseUrl()
    {
        $filename = basename($this->getServer('SCRIPT_FILENAME'));

        if (basename($this->getServer('SCRIPT_NAME')) === $filename) {
            $baseUrl = $this->getServer('SCRIPT_NAME');
        } elseif (basename($this->getServer('PHP_SELF')) === $filename) {
            $baseUrl = $this->getServer('PHP_SELF');
        } elseif (basename($this->getServer('ORIG_SCRIPT_NAME')) === $filename) {
            $baseUrl = $this->getServer('ORIG_SCRIPT_NAME');
        } else {
            $baseUrl = '';
        }

        return rtrim($baseUrl, '/');
    }


    /**
     * 获取项目基础路径
     *
     * @return string
     */
    public function getBasePath()
    {
        $path = dirname($this->getBaseUrl());
        if ('\\' == DIRECTORY_SEPARATOR) {
            $path = strtr($path, '\\', '/');
        }

        return rtrim($path, '/');
    }

    /**
     * 获取PATHINFO
     *
     * @return mixed
     */
    public function getPathInfo()
    {
        return $this->getServer('PATH_INFO');
    }

    //返回是否HTTPS连接
    public function isHttps()
    {
        return isset($_SERVER['HTTPS']) && (strcasecmp($_SERVER['HTTPS'], 'on') === 0 || $_SERVER['HTTPS'] == 1)
        || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strcasecmp($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') === 0;
    }

    /**
     * 是否AJAX请求
     *
     * @return bool
     */
    public function isAjax()
    {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 'xmlhttprequest' == strtolower($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            return true;
        }
        return false;
    }

    /**
     * 获取http原始请求内容
     */
    public function getRawBody()
    {
        return file_get_contents('php://input');
    }

    /**
     * 返回键是否在GET/POST中
     * @param string $name
     * @return boolean
     */
    public function __isset($name)
    {
        return (isset($_GET[$name]) || isset($_POST[$name]));
    }
}
