<?php
namespace Core\Http;

class Headers implements \IteratorAggregate, \Countable
{
    protected $headers = array();
    protected static $special = array(
        'CONTENT_TYPE',
        'CONTENT_LENGTH',
        'PHP_AUTH_USER',
        'PHP_AUTH_PW',
        'PHP_AUTH_DIGEST',
        'AUTH_TYPE'
    );

    public function __construct(array $headers = array())
    {
        $this->headers = $headers;
    }

    public static function createFromEnv()
    {
        $header = new self();
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'X_') === 0 || strpos($key, 'HTTP_') === 0 || in_array($key, self::$special)) {
                $header->set($key, $value);
            }
        }
        return $header;
    }

    public function set($name, $value)
    {
        $name = $this->normalizeKey($name);
        if (!is_string($name) || !is_string($value)) {
            throw new \UnexpectedValueException('参数值必须是字符串');
        }
        $this->headers[$name] = $value;
    }

    public function get($name)
    {
        $name = $this->normalizeKey($name);
        return isset($this->headers[$name]) ? $this->headers[$name] : null;
    }

    public function remove($name)
    {
        unset($this->headers[$this->normalizeKey($name)]);
    }

    public function has($name)
    {
        return isset($this->headers[$this->normalizeKey($name)]);
    }

    public function normalizeKey($key)
    {
        $key = strtolower($key);
        $key = str_replace(array('-', '_'), ' ', $key);
        $key = preg_replace('#^http #', '', $key);
        $key = ucwords($key);
        $key = str_replace(' ', '-', $key);
        return $key;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->headers);
    }

    public function count()
    {
        return count($this->headers);
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
