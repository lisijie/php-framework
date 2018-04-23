<?php
namespace Core\Http;

use Psr\Http\Message\UriInterface;

/**
 * URI
 *
 * @see https://zh.wikipedia.org/wiki/%E7%BB%9F%E4%B8%80%E8%B5%84%E6%BA%90%E6%A0%87%E5%BF%97%E7%AC%A6
 * @package Core\Http
 */
class Uri implements UriInterface
{
    /**
     * @var string
     */
    private $scheme = '';

    /**
     * @var string
     */
    private $user = '';

    /**
     * @var string
     */
    private $pass = '';

    /**
     * @var string
     */
    private $host = '';

    /**
     * @var int|null
     */
    private $port;

    /**
     * @var string Uri path.
     */
    private $path = '';

    /**
     * @var string Uri query string.
     */
    private $query = '';

    /**
     * @var string Uri fragment.
     */
    private $fragment = '';

    protected static $defaultSchemePorts = [
        '' => 0,
        'https' => 443,
        'http' => 80,
    ];

    public function __construct($uri = '')
    {
        if ($uri) {
            $parts = parse_url($uri);
            if (!$parts) {
                throw new \InvalidArgumentException("Unable to parse uri: {$uri}");
            }
            $this->applyParts($parts);
        }
    }

    protected function applyParts(array $parts)
    {
        if (isset($parts['scheme'])) {
            $this->scheme = $this->filterScheme($parts['scheme']);
        }
        if (isset($parts['host'])) {
            $this->host = $this->filterHost($parts['host']);
        }
        if (isset($parts['port'])) {
            $this->port = $this->filterPort($parts['port']);
        }
        if (isset($parts['user'])) {
            $this->user = $parts['user'];
        }
        if (isset($parts['pass'])) {
            $this->pass = $parts['pass'];
        }
        if (isset($parts['path'])) {
            $this->path = $this->filterPath($parts['path']);
        }
        if (isset($parts['query'])) {
            $this->query = $this->filterQuery($parts['query']);
        }
        if (isset($parts['fragment'])) {
            $this->fragment = $this->filterQuery($parts['fragment']);
        }
    }

    /**
     * 过滤URI Scheme
     *
     * 验证scheme是否支持，并统一转成小写形式。
     *
     * @param $scheme
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function filterScheme($scheme)
    {
        if (!is_string($scheme)) {
            throw new \InvalidArgumentException('Scheme must be a string');
        }
        $scheme = strtolower($scheme);
        if (!isset(self::$defaultSchemePorts[$scheme])) {
            throw new \InvalidArgumentException('Scheme must be one of: "", "http", "https"');
        }
        return $scheme;
    }

    /**
     * 过滤Host名
     *
     * @param $host
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function filterHost($host)
    {
        if (!is_string($host)) {
            throw new \InvalidArgumentException('Host must be a string');
        }
        return strtolower($host);
    }

    /**
     * 过滤端口
     *
     * @param $port
     * @return int|null
     * @throws \InvalidArgumentException
     */
    protected function filterPort($port)
    {
        if (is_null($port) || (is_int($port) && $port > 0 && $port <= 65535)) {
            return $port;
        }
        throw new \InvalidArgumentException('Port must be null or an integer between 1 and 65535 (inclusive)');
    }

    /**
     * 过滤URI Path
     *
     * 对特殊字符进行url编码
     *
     * @param string $path
     * @return string
     * @throws \InvalidArgumentException 参数非字符串时抛出异常
     */
    protected function filterPath($path)
    {
        if (!is_string($path)) {
            throw new \InvalidArgumentException('Path must be a string');
        }
        return preg_replace_callback(
            '/(?:[^a-zA-Z0-9_\-\.~:@&=\+\$,\/;%]+|%(?![A-Fa-f0-9]{2}))/',
            function ($match) {
                return rawurlencode($match[0]);
            },
            $path
        );
    }

    /**
     * 过滤查询字符串
     *
     * 对特殊字符进行URL编码
     *
     * @param string $query
     * @return string
     * @throws \InvalidArgumentException 参数非字符串时抛出异常
     */
    protected function filterQuery($query)
    {
        if (!is_string($query)) {
            throw new \InvalidArgumentException('Query must be a string');
        }
        $query = ltrim($query, '?');
        return preg_replace_callback(
            '/(?:[^a-zA-Z0-9_\-\.~!\$&\'\(\)\*\+,;=%:@\/\?]+|%(?![A-Fa-f0-9]{2}))/',
            function ($match) {
                return rawurlencode($match[0]);
            },
            $query
        );
    }

    /**
     * 过滤用户信息字符串
     *
     * @param string $query
     * @return string
     */
    protected function filterUserInfo($query)
    {
        $query = (string)$query;
        return preg_replace_callback(
            '/(?:[^a-zA-Z0-9_\-\.~!\$&\'\(\)\*\+,;=]+|%(?![A-Fa-f0-9]{2}))/u',
            function ($match) {
                return rawurlencode($match[0]);
            },
            $query
        );
    }

    /**
     * 当前scheme设置的端口是否默认端口
     *
     * @return bool
     */
    protected function isDefaultPort()
    {
        if ($this->port && $this->port === self::$defaultSchemePorts[$this->scheme]) {
            return true;
        }
        return false;
    }

    /**
     * 获取Scheme
     *
     * @return string
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * 返回URI的authority信息
     *
     * @return string 格式：[user-info@]host[:port]
     */
    public function getAuthority()
    {
        $userInfo = $this->getUserInfo();
        if ($userInfo) {
            $userInfo .= '@';
        }
        $host = $this->host;
        $port = '';
        if ($this->port && !$this->isDefaultPort()) {
            $port = ":{$this->port}";
        }
        return $userInfo . $host . $port;
    }

    /**
     * URI中的用户信息
     *
     * @return string
     */
    public function getUserInfo()
    {
        return $this->user ? ($this->user . ($this->pass ? ":{$this->pass}" : '')) : '';
    }

    /**
     * 返回URI中的主机名
     *
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * 获取端口
     *
     * @return int|null
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * 获取URI中的路径信息
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * 获取URI中的查询字符串
     *
     * @return string URL编码后的查询字符串
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * 获取URI中的fragment信息(#号后面的字符串)
     *
     * @return string
     */
    public function getFragment()
    {
        return $this->fragment;
    }

    /**
     * 返回指定了scheme的新对象
     *
     * @param string $scheme
     * @return static
     * @throws \InvalidArgumentException
     */
    public function withScheme($scheme)
    {
        $scheme = $this->filterScheme($scheme);
        $obj = clone $this;
        $obj->scheme = $scheme;
        return $obj;
    }

    /**
     * 返回指定了用户信息的新对象
     *
     * @param string $user
     * @param null|string $password
     * @return static
     */
    public function withUserInfo($user, $password = null)
    {
        $obj = clone $this;
        $obj->user = $this->filterUserInfo($user);
        if ($password !== null) {
            $obj->pass = $this->filterUserInfo($password);
        }
        return $obj;
    }

    /**
     * 返回指定了host信息的新对象
     *
     * 传入空的host相当于移除host
     *
     * @param string $host
     * @return static
     * @throws \InvalidArgumentException
     */
    public function withHost($host)
    {
        $host = $this->filterHost($host);
        $obj = clone $this;
        $obj->host = $host;
        return $obj;
    }

    /**
     * 返回指定了端口信息的新对象
     *
     * 如果 port 为 null，相当于移除端口信息。
     *
     * @param int $port
     * @return static
     * @throws \InvalidArgumentException 当端口号无效时
     */
    public function withPort($port)
    {
        $port = $this->filterPort($port);
        $obj = clone $this;
        $obj->port = $port;
        return $obj;
    }

    /**
     * 返回指定了路径信息的新对象
     *
     * @param string $path
     * @return static
     * @throws \InvalidArgumentException 当path无效时
     */
    public function withPath($path)
    {
        $path = $this->filterPath($path);
        $obj = clone $this;
        $obj->path = $path;
        return $obj;
    }

    /**
     * 返回指定了查询字符的新对象
     *
     * @param string $query 可以是URL编码过的，或是未编码过的
     * @return static
     * @throws \InvalidArgumentException 当参数无效时
     */
    public function withQuery($query)
    {
        $query = $this->filterQuery($query);
        $obj = clone $this;
        $obj->query = $query;
        return $obj;
    }

    /**
     * 返回指定了fragment的新对象
     *
     * @param string $fragment
     * @return static
     * @throws \InvalidArgumentException 当参数无效时
     */
    public function withFragment($fragment)
    {
        $fragment = $this->filterQuery($fragment);
        $obj = clone $this;
        $obj->fragment = $fragment;
        return $obj;
    }

    /**
     * 转成字符串
     *
     * @return string
     */
    public function __toString()
    {
        $uri = '';
        if ($this->scheme) {
            $uri .= $this->scheme . ':';
        }
        $authority = $this->getAuthority();
        if ($authority) {
            $uri .= '//' . $authority;
        }
        if ($authority) {
            $uri .= '/' . ltrim($this->path, '/');
        } else {
            $uri .= $this->path;
        }
        if ($this->query) {
            $uri .= '?' . $this->query;
        }
        if ($this->fragment) {
            $uri .= '#' . $this->fragment;
        }
        return $uri;
    }
}