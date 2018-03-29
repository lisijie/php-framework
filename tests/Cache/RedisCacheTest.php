<?php

require_once __DIR__ . '/TestCaseTrait.php';

class RedisCacheTest extends TestCase
{
    use TestCaseTrait;

    /**
     * @var \Core\Cache\CacheInterface
     */
    private $cache;

    public function setUp()
    {
        $config = ['prefix' => 'test_', 'host' => 'localhost'];
        $this->cache = new \Core\Cache\RedisCache($config);
    }
}