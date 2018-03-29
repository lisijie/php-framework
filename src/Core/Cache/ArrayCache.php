<?php
namespace Core\Cache;

/**
 * 数组缓存
 *
 * 注意，如果使用不当，可能会造成内存超出上限，特别是在cli模式下。
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Cache
 */
class ArrayCache extends AbstractCache
{
    private $data = [];
    private $expires = [];

    protected function init()
    {
    }

    public function getCount()
    {
        return count($this->data);
    }

    protected function doAdd($key, $value, $ttl = 0)
    {
        if ($this->doHas($key)) {
            return false;
        }
        $this->data[$key] = $value;
        if ($ttl) {
            $this->expires[$key] = time() + $ttl;
        }
        return true;
    }

    protected function doSetMultiple(array $values, $ttl = 0)
    {
        $expire = time() + $ttl;
        foreach ($values as $key => $value) {
            $this->data[$key] = $value;
            if ($ttl) {
                $this->expires[$key] = $expire;
            }
        }
        return true;
    }

    protected function doGetMultiple(array $keys, $default = null)
    {
        $data = [];
        foreach ($keys as $key) {
            $data[$key] = array_key_exists($key, $this->data) ? $this->data[$key] : $default;
        }
        return $data;
    }

    protected function doDeleteMultiple(array $keys)
    {
        foreach ($keys as $key) {
            unset($this->data[$key]);
            unset($this->expires[$key]);
        }
        return true;
    }

    protected function doIncrement($key, $step = 1)
    {
        if (!$this->doHas($key) || $this->isExpire($key)) {
            $this->data[$key] = $step;
            return $step;
        } else {
            $this->data[$key] = (int)$this->data[$key] + $step;
            return $this->data[$key];
        }
    }

    protected function doDecrement($key, $step = 1)
    {
        if (!$this->doHas($key) || $this->isExpire($key)) {
            $this->data[$key] = 0 - $step;
        } else {
            $this->data[$key] = (int)$this->data[$key] - $step;
            return $this->data[$key];
        }
        return $this->data[$key];
    }

    protected function doClear()
    {
        $this->data = $this->expires = [];
        return true;
    }

    protected function doHas($key)
    {
        return array_key_exists($key, $this->data) && !$this->isExpire($key);
    }

    private function isExpire($key)
    {
        if (isset($this->expires[$key]) && $this->expires[$key] > time()) {
            return true;
        }
        return false;
    }
}
