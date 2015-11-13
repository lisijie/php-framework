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

    protected $scriptUrl;

    protected $clientIP;

    protected $hostName;

    protected $hostInfo;

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
        return isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
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
        return (int) $_SERVER['SERVER_PORT'];
    }

    /**
     * 获取查询字符串
     *
     * @return string
     */
    public function getQueryString()
    {
        return isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
    }

    /**
     * 获取请求URI
     *
     * @return string
     */
    public function getRequestUri()
    {
        return isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    }

    /**
     * 获取查询参数
     *
     * @param string $name
     * @param mixed $default
     * @param bool $filter
     * @return mixed|null
     */
    public function getQuery($name = null, $default = null, $filter = true)
    {
        if (null === $name) {
            return $filter ? $this->applyFilter($_GET) : $_GET;
        }
        if (isset($_GET[$name])) {
            return $filter ? $this->applyFilter($_GET[$name]) : $_GET[$name];
        }
        return $default;
    }

    /**
     * 获取POST参数
     *
     * @param string $name
     * @param mixed $default
     * @param bool $filter 是否应用过滤器
     * @return mixed
     */
    public function getPost($name = null, $default = null, $filter = true)
    {
        if (null === $name) {
            return $filter ? $this->applyFilter($_POST) : $_POST;
        }
        if (isset($_POST[$name])) {
            return $filter ? $this->applyFilter($_POST[$name]) : $_POST[$name];
        }
        return $default;
    }

    /**
     * 获取上传的文件
     *
     * @param $name
     * @return array|null
     */
    public function getFiles($name = null)
    {
        return null === $name ? $_FILES : (isset($_FILES[$name]) ? $_FILES[$name] : null);
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
     * 获取$_SERVER环境变量
     *
     * @param string $name 键名
     * @param mixed $default 默认值
     * @param bool $filter 是否应用过滤器
     * @return mixed 值`
     */
    public function getServer($name = null, $default = null, $filter = true)
    {
        if (null === $name) {
            $value = $_SERVER;
        } else {
            $value = isset($_SERVER[$name]) ? $_SERVER[$name] : $default;
        }
        return $filter ? $this->applyFilter($value) : $value;
    }

    /**
     * 获取客户端IP地址
     *
     * @return string IP地址
     */
    public function getClientIp()
    {
        if ($this->clientIP === null) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR']) {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } elseif (isset($_SERVER['HTTP_CLIENT_IP']) && $_SERVER['HTTP_CLIENT_IP']) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } else {
                $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
            }
            $this->clientIP = preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $ip) ? $ip : 'unknown';
        }
        return $this->clientIP;
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
     * 获取请求来源地址
     *
     * @return string
     */
    public function getReferrer()
    {
        return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    }

    /**
     * 获取用户浏览器类型
     *
     * @return string
     */
    public function getUserAgent()
    {
        return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    }

    /**
     * 获取请求的Content-Type
     *
     * @return string
     */
    public function getContentType()
    {
        if (isset($_SERVER["CONTENT_TYPE"])) {
            return $_SERVER["CONTENT_TYPE"];
        } elseif (isset($_SERVER["HTTP_CONTENT_TYPE"])) {
            return $_SERVER["HTTP_CONTENT_TYPE"];
        }
        return '';
    }

    /**
     * 获取当前请求脚本的物理路径
     *
     * @return mixed
     */
    public function getScriptFile()
    {
        return $_SERVER['SCRIPT_FILENAME'];
    }

    /**
     * 获取当前请求脚本的URL
     *
     * 例如请求URL是 http://www.example.com:8080/app/index.php?r=main/test ，返回结果为 /app/index.php
     *
     * @return string
     */
    public function getScriptUrl()
    {
        if ($this->scriptUrl === null) {
            $scriptFile = $this->getScriptFile();
            $scriptName = basename($scriptFile);
            if (basename($_SERVER['SCRIPT_NAME']) === $scriptName) {
                $this->scriptUrl = $_SERVER['SCRIPT_NAME'];
            } elseif (basename($_SERVER['PHP_SELF']) === $scriptName) {
                $this->scriptUrl = $_SERVER['PHP_SELF'];
            } elseif (isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME']) === $scriptName) {
                $this->scriptUrl = $_SERVER['ORIG_SCRIPT_NAME'];
            } elseif (($pos = strpos($_SERVER['PHP_SELF'], '/' . $scriptName)) !== false) {
                $this->scriptUrl = substr($_SERVER['SCRIPT_NAME'], 0, $pos) . '/' . $scriptName;
            } elseif (!empty($_SERVER['DOCUMENT_ROOT']) && strpos($scriptFile, $_SERVER['DOCUMENT_ROOT']) === 0) {
                $this->scriptUrl = str_replace('\\', '/', str_replace($_SERVER['DOCUMENT_ROOT'], '', $scriptFile));
            }
        }
        return $this->scriptUrl;
    }

    /**
     * 获取访问主机名
     *
     * 例如请求URL是 http://www.example.com:8080/app/index.php?r=main/test ，返回结果为 www.example.com
     *
     * @return string
     */
    public function getHostName()
    {
        if ($this->hostName === null) {
            if (!($this->hostName = $this->getServer('HTTP_HOST'))) {
                if (!($this->hostName = $this->getServer('SERVER_NAME'))) {
                    $this->hostName = $this->getServer('SERVER_ADDR');
                }
            }
        }
        return $this->hostName;
    }

    /**
     * 获取主机URL
     *
     * 例如请求URL是 http://www.example.com:8080/app/index.php?r=main/test ，返回结果为 http://www.example.com:8080
     *
     * @return string
     */
    public function getHostInfo()
    {
        if ($this->hostInfo === null) {
            $isHttps = $this->isHttps();
            $port = $this->getServer('SERVER_PORT');
            $this->hostInfo = ($isHttps ? 'https://' : 'http://') . $this->getHostName() . ((($isHttps && $port != 443) || (!$isHttps && $port != 80)) ? ':' . $port : '');
        }
        return $this->hostInfo;
    }

    /**
     * 获取不包含域名和入口文件的URL
     *
     * 例如请求URL是 http://www.example.com:8080/app/index.php?r=main/test ，返回结果为 /app
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return rtrim(dirname($this->getScriptUrl()), '\\/');
    }

    /**
     * 获取PATHINFO
     *
     * 例如请求URL是 http://www.example.com/index.php/main/index?q=123 ，返回结果为 /main/index
     *
     * @return string
     */
    public function getPathInfo()
    {
        return isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
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

}
