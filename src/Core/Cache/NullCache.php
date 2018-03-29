<?php
namespace Core\Cache;

/**
 * 空缓存
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Cache
 */
class NullCache implements CacheInterface
{
    /**
     * {@inheritdoc}
     */
    public function add($key, $value, $ttl = 0)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function get($key, $default = null)
    {
        return $default;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $ttl = null)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple($keys, $default = null)
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $default;
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function setMultiple($values, $ttl = null)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMultiple($keys)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function increment($key, $step = 1)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function decrement($key, $step = 1)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        return false;
    }
}
