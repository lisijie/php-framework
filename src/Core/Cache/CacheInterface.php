<?php

namespace Core\Cache;

/**
 * 缓存接口定义
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Cache
 */
interface CacheInterface
{
    /**
     * 增加一个元素，如果元素已存在将返回false
     *
     * @param string $key
     * @param mixed $value
     * @param int $ttl 缓存保留时间/秒，0为永久
     * @return bool 成功返回true，失败返回false
     */
    public function add($key, $value, $ttl = 0);

    /**
     * 写入一个元素, 如果元素已存在将被覆盖
     *
     * @param string $key 键名
     * @param mixed $value 值
     * @param int $ttl 缓存保留时间/秒，0为永久
     * @return bool 成功返回true，失败返回false
     */
    public function set($key, $value, $ttl = 0);

    /**
     * 批量写入多个键值
     *
     * @param array $items
     * @param int $ttl
     * @return int 返回成功写入的数量
     */
    public function mset(array $items, $ttl = 0);

    /**
     * 读取一个元素
     *
     * @param string $key 键名
     * @return mixed 成功返回储存的值，失败返回false
     */
    public function get($key);

    /**
     * 批量读取多个key值
     *
     * 读取失败的不返回。例如：
     * $cache->set('a', 1);
     * $cache->set('b', 2);
     * $result = $cache->mget(['a','b','c'];
     * 结果为：
     * array('a'=>1, 'b'=>2)
     *
     * @param array $keys
     * @return array 返回存在的key对应的值
     */
    public function mget(array $keys);

    /**
     * 删除给定的一个或多个key
     *
     * @param string $key 键名
     * @return int 返回成功删除的数量
     */
    public function del($key);

    /**
     * 增加数值元素的值
     *
     * @param string $key
     * @param int $step
     * @return int 返回新值
     */
    public function increment($key, $step = 1);

    /**
     * 减小数值元素的值
     *
     * @param string $key
     * @param int $step
     * @return int 返回新值
     */
    public function decrement($key, $step = 1);
}
