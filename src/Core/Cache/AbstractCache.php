<?php

namespace Core\Cache;

use Core\Component;

/**
 * 缓存支持
 *
 * @author lisijie <lsj86@qq.com>
 * @package core\Cache
 */
abstract class AbstractCache extends Component implements CacheInterface
{
    const EVENT_ADD = 'add';
    const EVENT_SET = 'set';
    const EVENT_MSET = 'mset';
    const EVENT_GET = 'get';
    const EVENT_MGET = 'mget';
    const EVENT_DEL = 'del';
    const EVENT_INCREMENT = 'increment';
    const EVENT_DECREMENT = 'decrement';

    /**
     * key前缀
     * @var string
     */
    protected $prefix = '';

    /**
     * 配置信息
     * @var array
     */
    protected $config = [];

    public function __construct($config)
    {
        $this->config = $config;
        $this->prefix = isset($config['prefix']) ? $config['prefix'] : '';
        $this->init();
    }

    /**
     * 获取key前缀
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * 设置key前缀
     * @param string $prefix
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * 增加一个元素，如果元素已存在将返回false
     *
     * @param string $key
     * @param mixed $value
     * @param int $ttl 缓存保留时间/秒，0为永久
     * @return bool 成功返回true，失败返回false
     */
    public function add($key, $value, $ttl = 0)
    {
        $result = $this->doAdd($key, $value, $ttl);
        $this->trigger(self::EVENT_ADD);
        return $result;
    }

    /**
     * 写入一个元素, 如果元素已存在将被覆盖
     *
     * @param string $key 键名
     * @param mixed $value 值
     * @param int $ttl 缓存保留时间/秒，0为永久
     * @return bool 成功返回true，失败返回false
     */
    public function set($key, $value, $ttl = 0)
    {
        $result = $this->doSet($key, $value, $ttl);
        $this->trigger(self::EVENT_SET);
        return $result;
    }

    /**
     * 批量写入多个键值
     *
     * @param array $items
     * @param int $ttl
     * @return int 返回成功写入的数量
     */
    public function mset(array $items, $ttl = 0)
    {
        $result = $this->doMSet($items, $ttl);
        $this->trigger(self::EVENT_MSET);
        return $result;
    }

    /**
     * 读取一个元素
     *
     * @param string $key 键名
     * @return mixed 成功返回储存的值，失败返回false
     */
    public function get($key)
    {
        $result = $this->doGet($key);
        $this->trigger(self::EVENT_GET);
        return $result;
    }

    /**
     * 批量读取多个key值
     *
     * @param array $keys
     * @return array 返回每个key对应的值
     */
    public function mget(array $keys)
    {
        $result = $this->doMGet($keys);
        $this->trigger(self::EVENT_MGET);
        return $result;
    }

    /**
     * 删除给定的一个或多个key
     *
     * @param string|array $key 键名
     * @return int 返回成功删除的数量
     */
    public function del($key)
    {
        if (is_array($key)) {
            $keys = $key;
        } else {
            $keys = func_get_args();
        }
        $result = $this->doDel($keys);
        $this->trigger(self::EVENT_DEL);
        return $result;
    }

    /**
     * 增加数值元素的值
     *
     * @param string $key
     * @param int $step
     * @return int 返回新值
     */
    public function increment($key, $step = 1)
    {
        $result = $this->doIncrement($key, $step);
        $this->trigger(self::EVENT_INCREMENT);
        return $result;
    }

    /**
     * 减小数值元素的值
     *
     * @param string $key
     * @param int $step
     * @return int 返回新值
     */
    public function decrement($key, $step = 1)
    {
        $result = $this->doDecrement($key, $step);
        $this->trigger(self::EVENT_DECREMENT);
        return $result;
    }

    /**
     * 批量将key加上前缀
     * @param array $keys
     * @return array
     */
    protected function addPrefixKeys($keys)
    {
        if (strlen($this->prefix) == 0) {
            return $keys;
        }
        $newKeys = [];
        foreach ($keys as $key) {
            $newKeys[] = $this->prefix . $key;
        }
        return $newKeys;
    }

    /**
     * 将key/value数组的key加上前缀
     * @param array $items
     * @return array
     */
    protected function addPrefixItems($items)
    {
        if (strlen($this->prefix) == 0) {
            return $items;
        }
        $newItems = [];
        foreach ($items as $key => $value) {
            $newItems[$this->prefix . $key] = $value;
        }
        return $newItems;
    }

    abstract protected function init();

    abstract protected function doAdd($key, $value, $ttl);

    abstract protected function doSet($key, $value, $ttl);

    abstract protected function doMSet(array $array, $ttl);

    abstract protected function doGet($key);

    abstract protected function doMGet(array $keys);

    abstract protected function doDel(array $keys);

    abstract protected function doIncrement($key, $step = 1);

    abstract protected function doDecrement($key, $step = 1);
}
