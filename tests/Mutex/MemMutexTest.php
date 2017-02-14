<?php

class MemMutexTest extends TestCase
{
    /**
     * @var \Core\Mutex\MutexInterface
     */
    private $lock;

    public function setUp()
    {
        $options = [];
        $cache = \Core\Cache\Cache::factory('redis', $options);
        $this->lock = \Core\Mutex\MutexFactory::createMemMutex($cache);
    }

    /**
     * @expectedException \Core\Mutex\GetLockTimeoutException
     */
    public function testLockException()
    {
        $this->lock->lock('foo');
        $this->lock->lock('foo', 1);
    }

    public function testLock()
    {
        for ($i = 0; $i<10; $i++) {
            $this->lock->lock('foo2', 1);
            $this->lock->unlock('foo2');
        }
    }
}