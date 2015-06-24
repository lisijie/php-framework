<?php

namespace Core\Cache;

/**
 * 缓存适配器接口
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Cache
 */
interface CacheInterface
{

    /**
     * 构造方法
     *
     * @param array $options 参数
     */
    public function __construct($options);

    /**
     * 增加一个元素，如果元素已存在将返回false
     *
     * @param string $key
     * @param mixed $value
     * @param int $seconds 缓存保留时间/秒，0为永久
     * @return mixed
     */
    public function add($key, $value, $seconds = 0);

    /**
     * 写入一个元素, 如果元素已存在将被覆盖
     *
     * @param string $key 键名
     * @param mixed $value 值
     * @param int $seconds 缓存保留时间/秒，0为永久
     */
    public function set($key, $value, $seconds = 0);

    /**
     * 读取一个元素
     *
     * @param string $key 键名
     * @return mixed 成功返回储存的值，失败返回false
     */
    public function get($key);

    /**
     * 删除一个元素
     *
     * @param string $key 键名
     * @return boolean 成功返回true, 失败返回false
     */
    public function rm($key);

    /**
     * 增加数值元素的值
     *
     * @param string $key
     * @param int $value
     * @return mixed
     */
    public function increment($key, $value = 1);

    /**
     * 减小数值元素的值
     *
     * @param string $key
     * @param int $value
     * @return mixed
     */
    public function decrement($key, $value = 1);

    /**
     * 删除已经存储的所有的元素
     */
    public function flush();

    /**
     * 获取key前缀
     *
     * @return string
     */
    public function getPrefix();

}
