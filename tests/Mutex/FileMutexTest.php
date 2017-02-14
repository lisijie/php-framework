<?php

class FileMutexTest extends TestCase
{

    /**
     * @expectedException \Core\Mutex\GetLockTimeoutException
     */
    public function testLockException()
    {
        $lock = \Core\Mutex\MutexFactory::createFileMutex();
        $lock->lock('foo');
        $lock->lock('foo', 1);
    }

    public function testLock()
    {
        $lock = \Core\Mutex\MutexFactory::createFileMutex();
        for ($i = 0; $i<10; $i++) {
            $lock->lock('foo2', 1);
            $lock->unlock('foo2');
        }
    }
}