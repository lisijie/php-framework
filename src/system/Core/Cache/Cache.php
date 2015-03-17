<?php

namespace Core\Cache;


/**
 * 缓存支持
 *
 * @author lisijie <lsj86@qq.com>
 * @package core\Cache
 */
class Cache
{
    protected $handler;
    protected $prefix = '';

    /**
     * 创建缓存实例
     *
     * @param $driver
     * @param array $options
     * @return CacheInterface
     * @throws CacheException
     */
    public static function factory($driver, $options = array())
    {
        $class = '\\Core\\Cache\\Driver\\' . ucfirst($driver);
        if (!class_exists($class)) {
            throw new CacheException("不支持该缓存类型: {$driver}");
        }
        return new $class($options);
    }

    public function getPrefix()
    {
        return $this->prefix;
    }

    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    public function __call($method, $args)
    {
        if (method_exists($this->handler, $method)) {
            return call_user_func_array(array($this->handler, $method), $args);
        }
        throw new CacheException(__CLASS__ . "::{$method} 方法不存在！");
    }
}
