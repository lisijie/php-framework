<?php
namespace Core\Mutex;

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
