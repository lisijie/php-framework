<?php

require_once __DIR__ . '/TestCaseTrait.php';

class RedisClusterTest extends TestCase
{
    use TestCaseTrait;

    /**
     * @var \Core\Cache\CacheInterface
     */
    private $cache;

    public function setUp()
    {
        $config = [
            'servers' => ['localhost:7001', 'localhost:7002', 'localhost:7003']
        ];
        $this->cache = new \Core\Cache\RedisClusterCache($config);
    }
}