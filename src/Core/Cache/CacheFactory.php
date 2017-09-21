<?php
namespace Core\Cache;

class CacheFactory
{
    /**
     * 创建缓存对象
     *
     * @param string $type 类型
     * @param array $config 缓存配置
     * @return CacheInterface
     * @throws CacheException
     */
    public static function create($type, array $config)
    {
        switch (strtolower($type)) {
            case 'file':
                return new FileCache($config);
                break;
            case 'apc':
                return new ApcCache($config);
                break;
            case 'redis':
                return new RedisCache($config);
                break;
            case 'redis_cluster':
            case 'rediscluster':
                return new RedisClusterCache($config);
                break;
            case 'memcached':
                return new MemCache($config);
                break;
            default:
                throw new CacheException(__METHOD__ . "(): invalid type '{$type}'");
        }
    }
}