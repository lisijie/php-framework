<?php
namespace Core\Cache;

use Core\Component;

/**
 * 缓存支持
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Cache
 */
abstract class AbstractCache extends Component implements CacheInterface
{
    const EVENT_GET_MULTIPLE = 'get_multiple';
    const EVENT_SET_MULTIPLE = 'set_multiple';
    const EVENT_DELETE_MULTIPLE = 'delete_multiple';
    const EVENT_HAS = 'has';
    const EVENT_CLEAR = 'clear';
    const EVENT_ADD = 'add';
    const EVENT_INCREMENT = 'increment';
    const EVENT_DECREMENT = 'decrement';

    /**
     * key前缀
     * @var string
     */
    private $prefix = '';

    /**
     * 配置信息
     * @var array
     */
    protected $config = [];

    public function __construct(array $config = [])
    {
        $this->config = $config;
        if (isset($config['prefix'])) {
            if (!is_string($config['prefix'])) {
                throw new InvalidArgumentException('配置项prefix的值必须是string类型');
            }
            $this->prefix = $config['prefix'];
        }
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
     * 写入一个元素, 如果元素已存在将被覆盖
     *
     * @param string $key 键名
     * @param mixed $value 值
     * @param null|int|\DateInterval $ttl 缓存保留时间/秒，0为永久
     * @return bool 成功返回true，失败返回false
     */
    public function set($key, $value, $ttl = null)
    {
        return $this->setMultiple([$key => $value], $ttl);
    }

    /**
     * 批量写入多个键值
     *
     * @param array|\Traversable $values
     * @param null|int|\DateInterval $ttl 如果值小于0，则返回false
     * @return bool
     * @throws InvalidArgumentException
     */
    public function setMultiple($values, $ttl = null)
    {
        $ttl = $this->normalizeTTL($ttl);
        if (false === $ttl) {
            return false;
        }
        if (!is_array($values) && !($values instanceof \Traversable)) {
            throw new InvalidArgumentException('values参数必须为array或Traversable类型');
        }
        $items = [];
        foreach ($values as $key => $value) {
            if (!is_string($key)) {
                $key = (string)$key;
            }
            $items[$this->getKey($key)] = $value;
        }
        $result = $this->doSetMultiple($items, $ttl);
        $this->trigger(self::EVENT_SET_MULTIPLE);
        return $result;
    }

    /**
     * 读取一个元素
     *
     * @param string $key 键名
     * @param mixed $default
     * @return mixed 如果存在则返回储存的值，不存在返回默认值
     */
    public function get($key, $default = null)
    {
        $result = $this->getMultiple([$key], $default);
        return $result[$key];
    }

    /**
     * 批量读取多个key值
     *
     * @param array|\Traversable $keys
     * @param mixed $default
     * @return array 返回每个key对应的值
     * @throws InvalidArgumentException
     */
    public function getMultiple($keys, $default = null)
    {
        if ($keys instanceof \Traversable) {
            $keys = iterator_to_array($keys, false);
        } elseif (!is_array($keys)) {
            throw new InvalidArgumentException('keys参数必须为array或Traversable类型');
        }
        // 读取时加上前缀，返回结果去掉前缀
        $realKeys = $keyMap = [];
        foreach ($keys as $key) {
            $keyMap[$this->getKey($key)] = $key;
            $realKeys[] = $this->getKey($key);
        }
        $data = (array)$this->doGetMultiple($realKeys, $default);
        $result = [];
        foreach ($keyMap as $realKey => $key) {
            $result[$key] = array_key_exists($realKey, $data) ? $data[$realKey] : $default;
        }
        $this->trigger(self::EVENT_GET_MULTIPLE);
        return $result;
    }

    /**
     * 删除给定的key
     *
     * @param string $key 键名
     * @return bool
     */
    public function delete($key)
    {
        return $this->deleteMultiple([$key]);
    }

    /**
     * 批量删除多个key
     *
     * @param array|\Traversable $keys
     * @return bool
     * @throws InvalidArgumentException
     */
    public function deleteMultiple($keys)
    {
        if ($keys instanceof \Traversable) {
            $keys = iterator_to_array($keys, false);
        } elseif (!is_array($keys)) {
            throw new InvalidArgumentException('keys参数必须为array或Traversable类型');
        }
        foreach ($keys as &$key) {
            $key = $this->getKey($key);
        }
        $result = $this->doDeleteMultiple($keys);
        $this->trigger(self::EVENT_SET_MULTIPLE);
        return $result;
    }

    /**
     * 清空缓存
     *
     * @return bool
     */
    public function clear()
    {
        $result = $this->doClear();
        $this->trigger(self::EVENT_CLEAR);
        return $result;
    }

    /**
     * 检查key是否存在
     *
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        $result = $this->doHas($this->get($key));
        $this->trigger(self::EVENT_HAS);
        return $result;
    }

    /**
     * 增加一个元素，如果元素已存在将返回false
     *
     * @param string $key
     * @param mixed $value
     * @param int $ttl 缓存保留时间/秒，0为永久
     * @return bool 成功返回true，失败返回false
     */
    public function add($key, $value, $ttl = null)
    {
        $ttl = $this->normalizeTTL($ttl);
        if (false === $ttl) {
            return false;
        }
        $result = $this->doAdd($this->getKey($key), $value, $ttl);
        $this->trigger(self::EVENT_ADD);
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
        $result = $this->doIncrement($this->getKey($key), $step);
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
        $result = $this->doDecrement($this->getKey($key), $step);
        $this->trigger(self::EVENT_DECREMENT);
        return $result;
    }

    /**
     * 将key加上前缀
     *
     * @param string $key
     * @return string
     */
    private function getKey($key)
    {
        if ('' === $this->prefix) {
            return $key;
        }
        return $this->prefix . $key;
    }

    /**
     * 将ttl参数转成int类型，如果小于0，则返回false
     *
     * @param $ttl
     * @return bool|int
     * @throws InvalidArgumentException
     */
    private function normalizeTTL($ttl)
    {
        if (null === $ttl) {
            return 0;
        }
        if ($ttl instanceof \DateInterval) {
            $ttl = (int)\DateTime::createFromFormat('U', 0)->add($ttl)->format('U');
        }
        if (is_int($ttl)) {
            return $ttl >= 0 ? $ttl : false;
        }
        throw new InvalidArgumentException('ttl 参数类型必须是整数, DateInterval 或 null.');
    }

    /**
     * 初始化
     */
    abstract protected function init();

    /**
     * 新增
     *
     * @param $key
     * @param $value
     * @param int $ttl
     * @return bool
     */
    abstract protected function doAdd($key, $value, $ttl = 0);

    /**
     * 批量设置
     *
     * @param array $values
     * @param int $ttl
     * @return bool
     */
    abstract protected function doSetMultiple(array $values, $ttl = 0);

    /**
     * 批量读取多个值
     *
     * @param array $keys
     * @param null $default
     * @return array
     */
    abstract protected function doGetMultiple(array $keys, $default = null);

    /**
     * 批量删除
     *
     * @param array $keys
     * @return bool
     */
    abstract protected function doDeleteMultiple(array $keys);

    /**
     * 清空
     *
     * @return bool
     */
    abstract protected function doClear();

    /**
     * 检查是否存在
     *
     * @param string $key
     * @return bool
     */
    abstract protected function doHas($key);

    /**
     * 自增
     *
     * @param string $key
     * @param int $step
     * @return int
     */
    abstract protected function doIncrement($key, $step = 1);

    /**
     * 自减
     *
     * 注意某些组件（如Memcached），自减到0后再减，仍然返回0，而不是负数。
     *
     * @param string $key
     * @param int $step
     * @return int
     */
    abstract protected function doDecrement($key, $step = 1);
}
