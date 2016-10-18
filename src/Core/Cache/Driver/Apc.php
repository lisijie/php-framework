<?php

namespace Core\Cache\Driver;

use Core\Cache\Cache;
use Core\Cache\CacheInterface;
use Core\Cache\CacheException;

/**
 * APC 缓存支持
 *
 * 详情请看：http://www.php.net/manual/zh/book.apc.php
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Cache
 */
class Apc extends Cache implements CacheInterface
{

    private $apcu = false;

    public function __construct($options)
    {
        $this->prefix = isset($options['prefix']) ? $options['prefix'] : '';
        $this->apcu = function_exists('apcu_fetch');
        if (!$this->apcu && !function_exists('apc_store')) {
            throw new CacheException("当前环境不支持 APC 缓存");
        }
    }

    public function add($key, $value, $seconds = 0)
    {
        $key = $this->prefix . $key;
        return $this->apcu ? apcu_add($key, $value, $seconds) : apc_add($key, $value, $seconds);
    }

    public function set($key, $value, $seconds = 0)
    {
        $key = $this->prefix . $key;
        return $this->apcu ? apcu_store($key, $value, $seconds) : apc_store($key, $value, $seconds);
    }


    public function get($key)
    {
        $key = $this->prefix . $key;
        return $this->apcu ? apcu_fetch($key) : apc_fetch($key);
    }

    public function rm($key)
    {
        $key = $this->prefix . $key;
        return $this->apcu ? apc_delete($key) : apcu_delete($key);
    }

    public function increment($key, $value = 1)
    {
        $key = $this->prefix . $key;
        return $this->apcu ? apcu_inc($key, $value) : apc_inc($key, $value);
    }

    public function decrement($key, $value = 1)
    {
        $key = $this->prefix . $key;
        return $this->apcu ? apcu_dec($key, $value) : apc_dec($key, $value);
    }

    public function flush()
    {
        return $this->apcu ? apcu_clear_cache() : apc_clear_cache('user');
    }
}
