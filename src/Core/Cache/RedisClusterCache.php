<?php
namespace Core\Cache;

/**
 * Redis v3 集群
 *
 * 配置：
 * $config = array(
 *     'prefix' => 键名前缀
 *     'servers' => ['192.168.1.1:6379','192.168.1.2:6379', '192.168.1.3:6379'],
 *     'options' => [],
 * )
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Cache
 */
class RedisClusterCache extends AbstractCache implements CacheInterface
{
    /**
     * @var \Redis
     */
    private $handler;

    public function init()
    {
        if (!class_exists('\\RedisCluster')) {
            throw new CacheException("当前环境不支持RedisCluster");
        }
        $this->handler = new \RedisCluster(null, $this->config['servers']);
        if (!empty($this->config['options'])) {
            foreach ($this->config['options'] as $name => $val) { // [\Redis::OPT_SERIALIZER => \Redis::SERIALIZER_PHP]
                $this->handler->setOption($name, $val);
            }
        }
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
            $result[$name] = $data[$key];
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
