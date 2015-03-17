<?php
namespace Core\Http;

class Cookies implements \IteratorAggregate, \Countable
{
    protected $data = array();
    protected $defaults = array(
        'value' => '',
        'expire' => 0,
        'domain' => null,
        'path' => null,
        'secure' => false,
        'httponly' => false
    );

    public function __construct(array $cookies = array())
    {
        if ($cookies) {
            foreach ($cookies as $key => $value) {
                if (is_string($value)) $value = array('value' => $value);
                $this->data[$key] = array_merge($this->defaults, $value);
            }
        }
    }

    public function set($name, $value)
    {
        if (is_string($value)) $value = array('value' => $value);
        $this->data[$name] = array_merge($this->defaults, $value);
    }

    public function get($name)
    {
        if (isset($this->data[$name])) {
            return $this->data[$name]['value'];
        }
        return null;
    }

    public function remove($name)
    {
        $this->set($name, array('expire' => time() - 86400));
    }

    public function has($name)
    {
        return isset($this->data[$name]);
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

    public function count()
    {
        return count($this->data);
    }

    public function parseValue($name, array $value)
    {
        $values = array();
        if (isset($value['expire'])) {
            if (is_string($value['expire'])) {
                $timestamp = strtotime($value['expire']);
            } else {
                $timestamp = (int)$value['expire'];
            }
            if ($timestamp !== 0) {
                $values[] = '; expires=' . gmdate('D, d-M-Y H:i:s e', $timestamp);
            }
        }
        if (isset($value['path']) && $value['path']) {
            $values[] = '; path=' . $value['path'];
        }
        if (isset($value['domain']) && $value['domain']) {
            $values[] = '; domain=' . $value['domain'];
        }
        if (isset($value['secure']) && $value['secure']) {
            $values[] = '; secure';
        }
        if (isset($value['httponly']) && $value['httponly']) {
            $values[] = '; HttpOnly';
        }
        return sprintf("%s=%s", urlencode($name), urlencode($value['value']) . implode('', $values));
    }

    public function __toString()
    {
        $cookies = array();
        foreach ($this->data as $name => $value) {
            $cookies[] = sprintf("Set-Cookie: %s", $this->parseValue($name, $value));
        }
        return implode("\r\n", $cookies);
    }
}
