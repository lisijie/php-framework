<?php

require __DIR__ . '/TestCaseTrait.php';

class FileCacheTest extends TestCase
{
    use TestCaseTrait;

    /**
     * @var \Core\Cache\CacheInterface
     */
    private $cache;

    public function setUp()
    {
        $config = ['prefix' => 'test_', 'save_path' => '/tmp/cache'];
        $this->cache = new \Core\Cache\FileCache($config);
    }
}