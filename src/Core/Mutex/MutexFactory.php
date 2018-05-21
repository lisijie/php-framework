<?php
namespace Core\Mutex;

use Core\Cache\CacheInterface;
use Core\Db;

/**
 * 工厂类
 *
 * 用于创建各种锁的对象
 *
 * @package Core\Mutex
 */
class MutexFactory
{
    /**
     * 创建基于文件的锁
     *
     * @param bool|true $autoUnlock 是否自动释放锁
     * @return FileMutex
     */
    public static function createFileMutex($autoUnlock = true)
    {
        return new FileMutex($autoUnlock);
    }

    /**
     * 创建基于内存的锁
     *
     * @param CacheInterface $cacheObject cache对象
     * @param bool|true $autoUnlock 是否自动释放锁
     * @param int $lockTime 锁定时间上限
     * @return MemMutex
     */
    public static function createMemMutex(CacheInterface $cacheObject, $autoUnlock = true, $lockTime = 0)
    {
        $mu = new MemMutex($autoUnlock);
        $mu->lockTime = $lockTime;
        $mu->setCache($cacheObject);
        return $mu;
    }

    /**
     * 创建基于MySQL的锁
     *
     * @param Db $db
     * @param bool|true $autoUnlock 是否自动释放锁
     * @return MysqlMutex
     */
    public static function createMysqlMutex(Db $db, $autoUnlock = true)
    {
        $mu = new MysqlMutex($autoUnlock);
        $mu->setDb($db);
        return $mu;
    }
}
