<?php
namespace Core\Mutex;

use App;

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
     * @param bool|true $autoUnlock
     * @return FileMutex
     */
    public static function createFileMutex($autoUnlock = true)
    {
        return new FileMutex($autoUnlock);
    }

    /**
     * 创建基于内存的锁
     *
     * @param bool|true $autoUnlock
     * @return MemMutex
     */
    public static function createMemMutex($cacheType = '', $autoUnlock = true)
    {
        $mu = new MemMutex($autoUnlock);
        if (empty($cacheType)) {
            $mu->setCache(App::cache());
        } else {
            $mu->setCache(App::cache($cacheType));
        }
        return $mu;
    }

    /**
     * 创建基于MySQL的锁
     *
     * @param string $dbNode
     * @param bool|true $autoUnlock
     * @return MysqlMutex
     */
    public static function createMysqlMutex($dbNode = '', $autoUnlock = true)
    {
        $mu = new MysqlMutex($autoUnlock);
        if (empty($dbNode)) {
            $mu->setDb(App::db());
        } else {
            $mu->setDb(App::db($dbNode));
        }
        return $mu;
    }
}
