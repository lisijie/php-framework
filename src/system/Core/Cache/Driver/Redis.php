<?php

namespace Core\Cache\Driver;

use Core\Cache\Cache;
use Core\Cache\CacheInterface;
use Core\Cache\CacheException;

/**
 * Redis 支持
 *
 * 配置：
 * $options = array(
 *        'prefix' => 键名前缀
 *        'host' => 主机地址
 *        'port' => 端口
 *        'timeout' => 超时时间
 * )
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Cache
 */
class Redis extends Cache implements CacheInterface
{

    public function __construct($options)
    {
        if (!class_exists('\\Redis')) {
            throw new CacheException("当前环境不支持Redis");
        }

        $this->prefix = isset($options['prefix']) ? $options['prefix'] : '';
        $host = isset($options['host']) ? $options['host'] : '127.0.0.1';
        $port = isset($options['port']) ? intval($options['port']) : 6379;
        $timeout = isset($options['timeout']) ? floatval($options['timeout']) : 0.0;

        $this->handler = new \Redis();
        $this->handler->connect($host, $port, $timeout);
        $this->handler->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
    }

    public function add($key, $value, $seconds = 0)
    {
        $ret = $this->handler->setnx($this->prefix . $key, $value);
        if ($ret && $seconds > 0) {
            $this->handler->expire($this->prefix . $key, $seconds);
        }
        return $ret;
    }

    public function set($key, $value, $seconds = 0)
    {
        $ret = $this->handler->set($this->prefix . $key, $value, $seconds);
        if ($ret && $seconds > 0) {
            $this->handler->expire($this->prefix . $key, $seconds);
        }
        return $ret;
    }

    public function get($key)
    {
        return $this->handler->get($this->prefix . $key);
    }

    public function rm($key)
    {
        return $this->handler->delete($this->prefix . $key);
    }

    public function flush()
    {
        return $this->handler->flushDB();
    }

    public function increment($key, $value = 1)
    {
        return $this->handler->incrBy($this->prefix . $key, $value);
    }

    public function decrement($key, $value = 1)
    {
        return $this->handler->decrBy($this->prefix . $key, $value);
    }
}
