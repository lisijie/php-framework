<?php
namespace Core\Http;

class Headers
{
    /**
     * @var array
     */
    protected $headers = [];

    /**
     * @var array
     */
    protected $headerNames = [];

    /**
     * @var array
     */
    protected static $specials = [
        'CONTENT_TYPE',
        'CONTENT_LENGTH',
        'PHP_AUTH_USER',
        'PHP_AUTH_PW',
        'PHP_AUTH_DIGEST',
        'AUTH_TYPE'
    ];

    public function __construct(array $headers = [])
    {
        foreach ($headers as $name => $value) {
            $this->set($name, $value);
        }
    }

    /**
     * 根据请求的环境变量创建对象
     *
     * @return Headers
     */
    public static function createFromGlobals()
    {
        $header = new self();
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'X_') === 0 || strpos($key, 'HTTP_') === 0 || in_array($key, self::$specials)) {
                $key = strtolower($key);
                $key = str_replace(['-', '_'], ' ', $key);
                $key = preg_replace('#^http #', '', $key);
                $key = ucwords($key);
                $key = str_replace(' ', '-', $key);
                $header->set($key, $value);
            }
        }
        return $header;
    }

    /**
     * 设置header
     *
     * @param string $name 大小写不敏感的header字段名
     * @param string|string[] $value header值
     * @return static
     * @throws \InvalidArgumentException 名称或值无效时抛出
     */
    public function set($name, $value)
    {
        if (empty($name)) {
            throw new \InvalidArgumentException('Header name cannot be empty');
        }
        if (!is_array($value)) {
            $value = [$value];
        }
        $normalized = strtolower($name);
        if (isset($this->headerNames[$normalized])) {
            unset($this->headers[$this->headerNames[$normalized]]);
        }
        $this->headers[$name] = $value;
        $this->headerNames[$normalized] = $name;
        return $this;
    }

    /**
     * 给指定header附加新值
     *
     * @param string $name 大小写不敏感的header字段名
     * @param string|string[] $value 要附加的header值
     * @return static
     * @throws \InvalidArgumentException 名称或值无效时抛出
     */
    public function add($name, $value)
    {
        $normalized = strtolower($name);
        if (!is_array($value)) {
            $value = [$value];
        }
        if (!isset($this->headerNames[$normalized])) {
            $this->headerNames[$normalized] = $name;
            $this->headers[$name] = [];
        }
        foreach ($value as $item) {
            $this->headers[$name][] = (string)$item;
        }
        return $this;
    }

    /**
     * 返回指定header信息
     *
     * @param string $name 大小写不敏感的header字段名
     * @return string[]
     */
    public function get($name)
    {
        $name = strtolower($name);
        if (!isset($this->headerNames[$name])) {
            return [];
        }
        return $this->headers[$this->headerNames[$name]];
    }

    /**
     * 移除指定header
     *
     * @param string $name 大小写不敏感的header字段名
     * @return static
     */
    public function remove($name)
    {
        $normalized = strtolower($name);
        if (!isset($this->headerNames[$normalized])) {
            return $this;
        }
        unset($this->headers[$this->headerNames[$normalized]]);
        unset($this->headerNames[$normalized]);
        return $this;
    }

    /**
     * 返回所有header信息
     *
     * @return string[][]
     */
    public function all()
    {
        return $this->headers;
    }

    /**
     * 检查指定header是否存在
     *
     * @param string $name 大小写不敏感的名称
     * @return bool
     */
    public function has($name)
    {
        return isset($this->headerNames[strtolower($name)]);
    }

    public function __toString()
    {
        $str = '';
        foreach ($this->headers as $key => $value) {
            $str .= "{$key}: {$value}\n";
        }
        return $str;
    }
}
