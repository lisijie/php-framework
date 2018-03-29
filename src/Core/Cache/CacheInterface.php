<?php
namespace Core\Cache;

use Psr\SimpleCache\CacheInterface as SimpleCacheInterface;

/**
 * 缓存接口定义
 *
 * 继承PSR-16的SimpleCache接口，增加 add、increment、decrement 方法。
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Cache
 */
interface CacheInterface extends SimpleCacheInterface
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
     * 增加数值元素的值
     *
     * 如果指定的 key 不存在，则自动创建。
     *
     * @param string $key
     * @param int $step
     * @return int|bool 返回新值，失败返回false
     */
    public function increment($key, $step = 1);

    /**
     * 减小数值元素的值
     *
     * 如果指定的 key 不存在，则自动创建。
     *
     * @param string $key
     * @param int $step
     * @return int|bool 返回新值，失败返回false
     */
    public function decrement($key, $step = 1);
}
