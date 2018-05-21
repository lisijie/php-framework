<?php
namespace Core\Mutex;

use Core\Cache\CacheInterface;

class MemMutex extends MutexAbstract
{
    /**
     * @var CacheInterface
     */
    public $cache;

    public $lockTime = 0;

    private $prefix = 'lock_';

    public function setCache(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    protected function doUnlock($name)
    {
        return $this->cache->delete($this->prefix . $name);
    }

    public function tryLock($name)
    {
        return $this->cache->add($this->prefix . $name, time(), $this->lockTime);
    }
}
