<?php

namespace Core\Cache;

/**
 * APC 缓存支持
 *
 * 详情请看：http://www.php.net/manual/zh/book.apc.php
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Cache
 */
class ApcCache extends AbstractCache
{
    private $apcu = false;

    public function init()
    {
        $this->prefix = isset($this->config['prefix']) ? $this->config['prefix'] : '';
        $this->apcu = function_exists('apcu_fetch');
        if (!$this->apcu && !function_exists('apc_store')) {
            throw new CacheException("当前环境不支持 APC 缓存");
        }
    }

    protected function doAdd($key, $value, $ttl)
    {
        $key = $this->prefix . $key;
        return $this->apcu ? apcu_add($key, $value, $ttl) : apc_add($key, $value, $ttl);
    }

    protected function doSet($key, $value, $ttl)
    {
        $key = $this->prefix . $key;
        return $this->apcu ? apcu_store($key, $value, $ttl) : apc_store($key, $value, $ttl);
    }

    protected function doMSet(array $items, $ttl)
    {
        $items = $this->addPrefixItems($items);
        // 返回错误的key
        $result = $this->apcu ? apcu_store($items, null, $ttl) : apc_store($items, null, $ttl);
        return count($items) - count($result);
    }

    protected function doGet($key)
    {
        $key = $this->prefix . $key;
        return $this->apcu ? apcu_fetch($key) : apc_fetch($key);
    }

    protected function doMGet(array $keys)
    {
        $keys = $this->addPrefixKeys($keys);
        $data = $this->apcu ? apcu_fetch($keys) : apc_fetch($keys);
        $result = [];
        foreach ($data as $key => $value) {
            $result[substr($key, strlen($this->prefix))] = $value;
        }
        return $result;
    }

    protected function doDel(array $keys)
    {
        // apc返回删除失败的key集合
        $result = $this->apcu ? apc_delete($this->addPrefixKeys($keys)) : apcu_delete($this->addPrefixKeys($keys));
        return count($keys) - count($result);
    }

    protected function doIncrement($key, $step = 1)
    {
        $key = $this->prefix . $key;
        return $this->apcu ? apcu_inc($key, $step) : apc_inc($key, $step);
    }

    protected function doDecrement($key, $step = 1)
    {
        $key = $this->prefix . $key;
        return $this->apcu ? apcu_dec($key, $step) : apc_dec($key, $step);
    }
}
