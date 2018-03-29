<?php
namespace Core\Cache;

/**
 * APC 缓存支持
 *
 * @author lisijie <lsj86@qq.com>
 * @link http://www.php.net/manual/zh/book.apc.php
 * @package Core\Cache
 */
class ApcCache extends AbstractCache
{
    private $apcu = false;

    public function init()
    {
        $this->apcu = function_exists('apcu_fetch');
        if (!$this->apcu && !function_exists('apc_store')) {
            throw new CacheException("当前环境不支持 APC 缓存");
        }
    }

    protected function doAdd($key, $value, $ttl = 0)
    {
        return $this->apcu ? apcu_add($key, $value, $ttl) : apc_add($key, $value, $ttl);
    }

    protected function doSetMultiple(array $values, $ttl = 0)
    {
        // 返回错误的key
        $this->apcu ? apcu_store($values, null, $ttl) : apc_store($values, null, $ttl);
        return true;
    }

    protected function doGetMultiple(array $keys, $default = null)
    {
        $data = $this->apcu ? apcu_fetch($keys) : apc_fetch($keys);
        return $data;
    }

    protected function doDeleteMultiple(array $keys)
    {
        $this->apcu ? apc_delete($keys) : apcu_delete($keys);
        return true;
    }

    protected function doIncrement($key, $step = 1)
    {
        if (!$this->doHas($key)) {
            $this->apcu ? apcu_store($key, 0) : apc_store($key, 0);
        }
        return $this->apcu ? apcu_inc($key, $step) : apc_inc($key, $step);
    }

    protected function doDecrement($key, $step = 1)
    {
        if (!$this->doHas($key)) {
            $this->apcu ? apcu_store($key, 0) : apc_store($key, 0);
        }
        return $this->apcu ? apcu_dec($key, $step) : apc_dec($key, $step);
    }

    protected function doClear()
    {
        return $this->apcu ? apcu_clear_cache() : apc_clear_cache('user');
    }

    protected function doHas($key)
    {
        return $this->apcu ? apcu_exists($key) : apc_exists($key);
    }
}
