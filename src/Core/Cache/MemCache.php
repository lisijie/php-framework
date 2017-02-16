<?php
namespace Core\Cache;

/**
 * memcached 支持
 *
 * 配置：
 * $config = array(
 *        'prefix' => 键名前缀
 *        'servers' => [
 *            ['192.168.1.1', 11211], //memcached服务器1
 *            ['192.168.1.2', 11211], //memcached服务器2
 *        ],
 * )
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Cache
 */
class MemCache extends AbstractCache
{
    /**
     * @var \Memcached
     */
    private $handler;

    public function init()
    {
        if (empty($this->config['servers'])) {
            throw new CacheException(__CLASS__ . " 缺少参数: servers");
        }
        if (!class_exists('\\Memcached')) {
            throw new CacheException("当前环境不支持: memcached");
        }
        $this->prefix = isset($this->config['prefix']) ? $this->config['prefix'] : '';

        $this->handler = new \Memcached();
        $this->handler->addServers($this->config['servers']);
        if (!isset($this->config['options'])) {
            $this->config['options'] = [];
        }
        $this->config['options'][\Memcached::OPT_DISTRIBUTION] = \Memcached::DISTRIBUTION_CONSISTENT;
        $this->handler->setOptions($this->config['options']);
    }

    protected function doAdd($key, $value, $ttl)
    {
        return $this->handler->add($this->prefix . $key, $value, $ttl);
    }

    protected function doSet($key, $value, $ttl)
    {
        return $this->handler->set($this->prefix . $key, $value, $ttl);
    }

    protected function doMSet(array $items, $ttl)
    {
        if (strlen($this->prefix) > 0) {
            foreach ($items as $key => $value) {
                $items[$this->prefix . $key] = $value;
                unset($items[$key]);
            }
        }
        if ($this->handler->setMulti($items, $ttl)) {
            return count($items);
        }
        return 0;
    }

    protected function doGet($key)
    {
        return $this->handler->get($this->prefix . $key);
    }

    protected function doMGet(array $keys)
    {
        $data = $this->handler->getMulti($this->addPrefixKeys($keys));
        $result = [];
        foreach ($data as $key => $value) {
            $result[substr($key, strlen($this->prefix))] = $value;
        }
        return $result;
    }

    protected function doDel(array $keys)
    {
        // 返回每个key是否删除成功
        $result = $this->handler->deleteMulti($this->addPrefixKeys($keys));
        $count = 0;
        foreach ($result as $key => $val) {
            if ($val == 1) {
                $count ++;
            }
        }
        return $count;
    }

    protected function doIncrement($key, $step = 1)
    {
        return $this->handler->increment($this->prefix . $key, $step);
    }

    protected function doDecrement($key, $step = 1)
    {
        return $this->handler->decrement($this->prefix . $key, $step);
    }
}
