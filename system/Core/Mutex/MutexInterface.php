<?php
namespace Core\Mutex;

interface MutexInterface
{
    /**
     * 获取锁
     *
     * 获取名称为$name的锁，成功返回true，失败返回false。
     *
     * @param string $name 锁名称
     * @param int $timeout 等待时间
     * @return bool
     */
    public function lock($name, $timeout = 0);

    /**
     * 释放锁
     *
     * @param string $name 名称
     * @return bool
     */
    public function unlock($name);

}
