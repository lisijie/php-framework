<?php

namespace Core\Cache\Driver;

use Core\Cache\Cache;
use Core\Cache\CacheInterface;
use Core\Cache\CacheException;

/**
 * memcached 支持
 *
 * 配置：
 * $options = array(
 *        'prefix' => 键名前缀
 *        'servers' => array(
 *            array('192.168.1.1', 11211), //memcached服务器1
 *            array('192.168.1.2', 11211), //memcached服务器2
 *        ),
 * )
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Cache
 */
class Memcached extends Cache implements CacheInterface
{

    public function __construct($options)
    {
        if (empty($options['servers'])) {
            throw new CacheException(__CLASS__ . " 缺少参数: servers");
        }
        if (!class_exists('\\Memcached')) {
            throw new CacheException("当前环境不支持: memcached");
        }
        $this->prefix = isset($options['prefix']) ? $options['prefix'] : '';

        $this->handler = new \Memcached();
        $this->handler->addServers($options['servers']);
        if (!isset($options['opts'])) {
            $options['opts'] = array();
        }
        $options['opts'][\Memcached::OPT_DISTRIBUTION] = \Memcached::DISTRIBUTION_CONSISTENT;
        $this->handler->setOptions($options['opts']);
    }

    public function add($key, $value, $seconds = 0)
    {
        return $this->handler->add($this->prefix . $key, $value, $seconds);
    }

    public function set($key, $value, $seconds = 0)
    {
        return $this->handler->set($this->prefix . $key, $value, $seconds);
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
        return $this->handler->flush();
    }

    public function increment($key, $value = 1)
    {
        return $this->handler->increment($this->prefix . $key, $value);
    }

    public function decrement($key, $value = 1)
    {
        return $this->handler->decrement($this->prefix . $key, $value);
    }
}
