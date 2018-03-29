<?php
namespace Core\Cache;

/**
 * Redis 支持
 *
 * 这里对值强制使用序列化，造成的副作用是不能 set 一个值后使用 increment、decrement，会返回false。
 *
 * 配置：
 * $config = array(
 *      'prefix' => 键名前缀,
 *      'host' => 主机地址,
 *      'port' => 端口,
 *      'auth' => 密码,
 *      'timeout' => 超时时间,
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
    protected $client;

    protected function init()
    {
        if (!class_exists('\\Redis')) {
            throw new CacheException("当前环境不支持Redis");
        }
        $host = isset($this->config['host']) ? $this->config['host'] : '127.0.0.1';
        $port = isset($this->config['port']) ? intval($this->config['port']) : 6379;
        $timeout = isset($this->config['timeout']) ? floatval($this->config['timeout']) : 0.0;

        $this->client = new \Redis();
        $this->client->connect($host, $port, $timeout);
        if (isset($this->config['auth'])) {
            $this->client->auth($this->config['auth']);
        }
        if (!empty($this->config['options'])) {
            foreach ($this->config['options'] as $name => $val) {
                $this->client->setOption($name, $val);
            }
        }
        $this->client->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
    }

    protected function doAdd($key, $value, $ttl = 0)
    {
        if ($this->client->setnx($key, $value)) {
            if ($ttl > 0) {
                $this->client->expire($key, $ttl);
            }
            return true;
        }
        return false;
    }

    protected function doSetMultiple(array $values, $ttl = 0)
    {
        if ($this->client->mset($values)) {
            if ($ttl > 0) {
                foreach ($values as $key => $val) {
                    $this->client->expire($key, $ttl);
                }
            }
            return true;
        }
        return false;
    }

    protected function doGetMultiple(array $keys, $default = null)
    {
        $values = $this->client->getMultiple($keys);
        $result = [];
        foreach ($keys as $idx => $key) {
            if (false === $values[$idx]) {
                $result[$key] = $default;
            } else {
                $result[$key] = $values[$idx];
            }
        }
        return $result;
    }

    protected function doDeleteMultiple(array $keys)
    {
        return $this->client->delete($keys) > 0;
    }

    protected function doClear()
    {
        if ($this->client instanceof \RedisCluster) {
            return false;
        }
        return $this->client->flushDB();
    }

    protected function doHas($key)
    {
        return $this->client->exists($key);
    }

    protected function doIncrement($key, $step = 1)
    {
        return $this->client->incrBy($key, $step);
    }

    protected function doDecrement($key, $step = 1)
    {
        return $this->client->decrBy($key, $step);
    }
}
