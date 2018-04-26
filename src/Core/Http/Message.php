<?php
namespace Core\Http;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

/**
 * HTTP消息
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Http
 */
abstract class Message implements MessageInterface
{

    /**
     * @var string
     */
    protected $protocol = '1.1';

    /**
     * @var Headers
     */
    protected $headers;

    /**
     * @var StreamInterface
     */
    protected $body;

    /**
     * 返回HTTP协议版本
     *
     * @return string
     */
    public function getProtocolVersion()
    {
        return $this->protocol;
    }

    /**
     * 返回带有指定版本号的新对象
     *
     * @param string $version HTTP protocol version
     * @return static
     */
    public function withProtocolVersion($version)
    {
        if ($version === $this->protocol) {
            return $this;
        }
        $obj = clone $this;
        $obj->protocol = (string)$version;
        return $obj;
    }

    /**
     * 返回所有header信息
     *
     * @return string[][]
     */
    public function getHeaders()
    {
        return $this->headers->all();
    }

    /**
     * 检查指定header是否存在
     *
     * @param string $name 大小写不敏感的名称
     * @return bool
     */
    public function hasHeader($name)
    {
        return $this->headers->has($name);
    }

    /**
     * 返回指定header信息
     *
     * @param string $name 大小写不敏感的header字段名
     * @return string[]
     */
    public function getHeader($name)
    {
        return $this->headers->get($name);
    }

    /**
     * 返回值为逗号分隔的字符串的header信息
     *
     * @param string $name 大小写不敏感的header字段名
     * @return string 如果header没有设置，将返回空字符串
     */
    public function getHeaderLine($name)
    {
        return implode(', ', $this->headers->get($name));
    }

    /**
     * 返回包含指定header的新实例
     *
     * @param string $name 大小写不敏感的header字段名
     * @param string|string[] $value header值
     * @return static
     * @throws \InvalidArgumentException 名称或值无效时抛出
     */
    public function withHeader($name, $value)
    {
        $obj = clone $this;
        $obj->headers->set($name, $value);
        return $obj;
    }

    /**
     * 返回包含指定header附加值的新实例
     *
     * @param string $name 大小写不敏感的header字段名
     * @param string|string[] $value 要附加的header值
     * @return static
     * @throws \InvalidArgumentException 名称或值无效时抛出
     */
    public function withAddedHeader($name, $value)
    {
        $obj = clone $this;
        $obj->headers->add($name, $value);
        return $obj;
    }

    /**
     * 返回去掉指定header的新实例
     *
     * @param string $name 大小写不敏感的header字段名
     * @return static
     */
    public function withoutHeader($name)
    {
        $obj = clone $this;
        $obj->headers->remove($name);
        return $obj;
    }

    /**
     * 返回消息体的Stream对象
     *
     * @return StreamInterface
     */
    public function getBody()
    {
        // TODO body未设置时创建默认值
        return $this->body;
    }

    /**
     * 返回指定消息体的新实例
     *
     * @param StreamInterface $body 消息体
     * @return static
     * @throws \InvalidArgumentException body无效时
     */
    public function withBody(StreamInterface $body)
    {
        if ($body === $this->body) {
            return $this;
        }
        $obj = clone $this;
        $obj->body = $body;
        return $obj;
    }

    /**
     * 克隆时需要将headers对象一并克隆
     */
    public function __clone()
    {
        $this->headers = clone $this->headers;
    }
}