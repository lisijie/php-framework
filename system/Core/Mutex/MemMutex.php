<?php
namespace Core\Mutex;

class MemMutex extends Mutex
{
    public $cache = 'default';

    public $lockTime = 0;

    private $prefix = 'lock_';

    protected function doLock($name, $timeout)
    {
        $waitTime = 0;
        while (!$this->memory()->add($this->prefix . $name, 1, $this->lockTime)) {
            if (++$waitTime > $timeout) {
                return false;
            }
            sleep(1);
        }
        return true;
    }

    protected function doUnlock($name)
    {
        return $this->memory()->rm($this->prefix . $name);
    }

    private function memory()
    {
        return \App::cache($this->cache);
    }
}
