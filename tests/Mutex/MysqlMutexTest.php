<?php

class MysqlMutexTest extends TestCase
{
    /**
     * @var \Core\Mutex\MutexInterface
     */
    private $lock;

    public function setUp()
    {
        $this->lock = \Core\Mutex\MutexFactory::createMysqlMutex(App::db());
    }

    /**
     * @expectedException \Core\Mutex\GetLockTimeoutException
     */
    public function testLockException()
    {
        $db = App::Db();
        $db->select("SELECT GET_LOCK(?, ?)", ['foo', 10], false); // 连接1
        $this->lock->lock('foo', 1); // 连接2
    }
}