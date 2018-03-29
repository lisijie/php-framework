<?php

require_once __DIR__ . '/TestCaseTrait.php';

class ArrayCacheTest extends TestCase
{
    use TestCaseTrait;

    /**
     * @var \Core\Cache\CacheInterface
     */
    private $cache;

    public function setUp()
    {
        $config = ['prefix' => 'test_'];
        $this->cache = new \Core\Cache\ArrayCache($config);
    }
}