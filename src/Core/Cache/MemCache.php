<?php
namespace Core\Cache;

/**
 * memcached 支持
 *
 * 配置：
 * $config = [
 *     'servers' => [
 *        ['192.168.1.1', 11211], //memcached服务器1
 *        ['192.168.1.2', 11211], //memcached服务器2
 *    ],
 * ]
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Cache
 */
class MemCache extends AbstractCache
{
    /**
     * @var \Memcached
     */
    private $client;

    public function init()
    {
        if (empty($this->config['servers'])) {
            throw new CacheException(__CLASS__ . " 缺少参数: servers");
        }
        if (!class_exists('\\Memcached')) {
            throw new CacheException("当前环境不支持: memcached");
        }
        $this->client = new \Memcached();
        $this->client->addServers($this->config['servers']);
        if (!isset($this->config['options'])) {
            $this->config['options'] = [];
        }
        $this->config['options'][\Memcached::OPT_DISTRIBUTION] = \Memcached::DISTRIBUTION_CONSISTENT;
        $this->client->setOptions($this->config['options']);
    }

    protected function doSetMultiple(array $values, $ttl = 0)
    {
        return $this->checkStatusCode($this->client->setMulti($values, $ttl));
    }

    protected function doGetMultiple(array $keys, $default = null)
    {
        return $this->checkStatusCode($this->client->getMulti($keys));
    }

    protected function doDeleteMultiple(array $keys)
    {
        $ok = true;
        $result = $this->client->deleteMulti($keys);
        foreach ($this->checkStatusCode($result) as $code) {
            if (true !== $code && $code !== \Memcached::RES_SUCCESS && $code !== \Memcached::RES_NOTFOUND) {
                $ok = false;
            }
        }
        return $ok;
    }

    protected function doClear()
    {
        return $this->client->flush();
    }

    protected function doHas($key)
    {
        if (false !== $this->client->get($key)) {
            return true;
        }
        if ($this->client->getResultCode() == \Memcached::RES_NOTFOUND) {
            return false;
        } elseif ($this->client->getResultCode() == \Memcached::RES_SUCCESS) {
            return true;
        } else {
            throw new CacheException('memcached client error: ' . $this->client->getResultMessage());
        }
    }

    protected function doIncrement($key, $step = 1)
    {
        if ($this->client->add($key, $step)) {
            return $step;
        }
        return $this->client->increment($key, $step);
    }

    protected function doDecrement($key, $step = 1)
    {
        if ($this->client->add($key, 0 - $step)) {
            return 0 - $step;
        }
        return $this->client->decrement($key, $step);
    }

    protected function doAdd($key, $value, $ttl = 0)
    {
        $ok = $this->client->add($key, $value, $ttl);
        if (false === $ok && $this->client->getResultCode() != \Memcached::RES_NOTSTORED) {
            throw new CacheException('memcached client error: ' . $this->client->getResultMessage());
        }
        return $ok;
    }

    /**
     * 检查Memcached的状态码
     *
     * 如果状态码不是成功或key不存在的状态，可能服务器有问题，抛出异常。
     *
     * @param $result
     * @return mixed
     * @throws CacheException
     */
    private function checkStatusCode($result)
    {
        $code = $this->client->getResultCode();
        if ($code == \Memcached::RES_SUCCESS || $code == \Memcached::RES_NOTFOUND) {
            return $result;
        }
        throw new CacheException('memcached client error: ' . $this->client->getResultMessage());
    }
}
