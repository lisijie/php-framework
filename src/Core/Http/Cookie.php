<?php
namespace Core\Http;

/**
 * Cookie
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Http
 */
class Cookie
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $value;

    /**
     * @var int
     */
    private $expire = 0;

    /**
     * @var string
     */
    private $domain = '';

    /**
     * @var string
     */
    private $path = '';

    /**
     * @var bool
     */
    private $secure = false;

    /**
     * @var bool
     */
    private $httpOnly = true;

    public function __construct($name, $value, $expire = 0, $domain = '', $path = '/', $secure = false, $httpOnly = true)
    {
        $name = (string)$name;
        if (preg_match('/[=,; \t\r\n\013\014]+/', $name)) {
            throw new \InvalidArgumentException('Cookie names cannot contain any of the following \'=,; \t\r\n\013\014\'');
        }
        $this->name = $name;
        $this->value = (string)$value;
        $this->expire = (int)$expire;
        $this->domain = $domain;
        $this->path = $path;
        $this->secure = $secure;
        $this->httpOnly = $httpOnly;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = (string)$value;
    }

    /**
     * @param int $expire
     */
    public function setExpire($expire)
    {
        $this->expire = (int)$expire;
    }

    /**
     * @param string $domain
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;
    }

    /**
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * @param bool $secure
     */
    public function setSecure($secure)
    {
        $this->secure = $secure;
    }

    /**
     * @param bool $httpOnly
     */
    public function setHttpOnly($httpOnly)
    {
        $this->httpOnly = $httpOnly;
    }

    /**
     * 格式化
     *
     * @return string
     */
    public function toHeader()
    {
        $values = [];
        if ($this->expire !== 0) {
            $values[] = '; expires=' . gmdate('D, d-M-Y H:i:s e', $this->expire);
        }
        if ($this->path !== '') {
            $values[] = '; path=' . $this->path;
        }
        if ($this->domain != '') {
            $values[] = '; domain=' . $this->domain;
        }
        if ($this->secure) {
            $values[] = '; secure';
        }
        if ($this->httpOnly) {
            $values[] = '; HttpOnly';
        }
        return sprintf("%s=%s", $this->name, urlencode($this->value) . implode('', $values));
    }

    public function __toString()
    {
        return sprintf("Set-Cookie: %s", $this->toHeader());
    }
}
