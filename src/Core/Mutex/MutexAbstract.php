<?php

namespace Core\Mutex;

/**
 * 互斥锁
 *
 * @package Core\Mutex
 */
abstract class MutexAbstract implements MutexInterface
{
    private $locks = [];
    private $autoUnlock = true;

    /**
     * 构造函数
     *
     * @param bool $autoUnlock 是否自动释放
     */
    public final function __construct($autoUnlock = true)
    {
        $this->autoUnlock = $autoUnlock;
        if ($this->autoUnlock) {
            $locks = &$this->locks;
            register_shutdown_function(function () use (&$locks) {
                foreach ($locks as $lock => $count) {
                    $this->unlock($lock);
                }
            });
        }
        $this->init();
    }

    protected function init()
    {
    }

    /**
     * 获取锁
     *
     * 获取名称为$name的锁，成功返回true，失败返回false。
     *
     * @param string $name 锁名称
     * @param int $timeout 等待时间
     * @throws GetLockTimeoutException
     * @return bool
     */
    public function lock($name, $timeout = 0)
    {
        $waitTime = 0;
        while (!$this->tryLock($name)) {
            if ($timeout && ++$waitTime > $timeout) {
                throw new GetLockTimeoutException($name, $timeout);
            }
            sleep(1);
        }
        $this->locks[$name] = true;
        return true;
    }

    /**
     * 释放锁
     *
     * @param string $name 名称
     * @return bool
     */
    public function unlock($name)
    {
        if ($this->doUnlock($name)) {
            unset($this->locks[$name]);
            return true;
        }
        return false;
    }

    abstract public function tryLock($name);

    abstract protected function doUnlock($name);

}