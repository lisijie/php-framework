<?php

require __DIR__ . '/TestCaseTrait.php';

class MemCacheTest extends TestCase
{
    use TestCaseTrait;

    /**
     * @var \Core\Cache\CacheInterface
     */
    private $cache;

    public function setUp()
    {
        $config = ['prefix' => 'test_', 'servers' => [['localhost',11211]]];
        $this->cache = new \Core\Cache\MemCache($config);
    }
}