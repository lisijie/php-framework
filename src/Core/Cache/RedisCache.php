<?php
namespace Core\Cache;

/**
 * Redis 支持
 *
 * 配置：
 * $config = array(
 *      'prefix' => 键名前缀
 *      'host' => 主机地址
 *      'port' => 端口
 *      'timeout' => 超时时间
 *      'options' => [], // redis设置选项
 * )
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Cache
 */
class RedisCache extends AbstractCache
{
    /**
     * @var \Redis
     */
    private $handler;

    public function init()
    {
        if (!class_exists('\\Redis')) {
            throw new CacheException("当前环境不支持Redis");
        }
        $host = isset($this->config['host']) ? $this->config['host'] : '127.0.0.1';
        $port = isset($this->config['port']) ? intval($this->config['port']) : 6379;
        $timeout = isset($this->config['timeout']) ? floatval($this->config['timeout']) : 0.0;

        $this->handler = new \Redis();
        $this->handler->connect($host, $port, $timeout);
        if (!empty($this->config['options'])) {
            foreach ($this->config['options'] as $name => $val) {
                $this->handler->setOption($name, $val);
            }
        }
        // 值使用php序列化，方便直接存储数组，但这样set一个值后将无法自增、自减
        $this->handler->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
    }

    protected function doAdd($key, $value, $ttl = 0)
    {
        $ret = $this->handler->setnx($this->prefix . $key, $value);
        if ($ret && $ttl > 0) {
            $this->handler->expire($this->prefix . $key, $ttl);
        }
        return $ret;
    }

    protected function doSet($key, $value, $ttl = 0)
    {
        $ret = $this->handler->set($this->prefix . $key, $value);
        if ($ret && $ttl > 0) {
            $this->handler->expire($this->prefix . $key, $ttl);
        }
        return $ret;
    }

    protected function doMSet(array $items, $ttl)
    {
        $items = $this->addPrefixItems($items);
        $ret = $this->handler->mset($items);
        if ($ret && $ttl > 0) {
            foreach ($items as $key => $val) {
                $this->handler->expire($key, $ttl);
            }
        }
        return $ret;
    }

    protected function doGet($key)
    {
        return $this->handler->get($this->prefix . $key);
    }

    protected function doMGet(array $keys)
    {
        $data = $this->handler->mget($this->addPrefixKeys($keys));
        $result = [];
        // 去掉前缀，返回key/value形式
        foreach ($keys as $key => $name) {
            if ($data[$key] !== false) { // 过滤掉不存在的key, mget一个不存在的key, 值为false
                $result[$name] = $data[$key];
            }
        }
        return $result;
    }

    protected function doDel(array $keys)
    {
        return $this->handler->del($this->addPrefixKeys($keys));
    }

    protected function doIncrement($key, $step = 1)
    {
        return $this->handler->incrBy($this->prefix . $key, $step);
    }

    protected function doDecrement($key, $step = 1)
    {
        return $this->handler->decrBy($this->prefix . $key, $step);
    }
}
