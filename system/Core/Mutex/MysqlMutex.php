<?php
namespace Core\Mutex;

/**
 * MySQL锁
 *
 * MySQL的GET_LOCK函数提供的锁是基于连接的，不同连接是互斥的，在同一个MySQL连接中，调用GET_LOCK()函数获取同一个锁永远返回1。
 * 因此，如果在使用MySQL长连接的应用中，可能不能达到预期的效果。当连接断开后，锁会自动释放掉。
 * $timeout 参数为当不能获取到锁时等待的时间/秒，在等待时间内，如果另一个连接释放了锁，则返回1，超过等待时间后仍没获取到锁，返回0。
 *
 * @package Core\Mutex
 */
class MysqlMutex extends Mutex
{
    public $db = 'default';

    protected function doLock($name, $timeout)
    {
        return (bool)$this->db()->getOne("SELECT GET_LOCK(?, ?)", array($name, $timeout), 0, true);
    }

    protected function doUnlock($name)
    {
        return (bool)$this->db()->getOne("SELECT RELEASE_LOCK(?)", array($name), 0, true);
    }

    private function db()
    {
        return \App::db($this->db);
    }
}
