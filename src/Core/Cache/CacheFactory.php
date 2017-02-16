<?php
namespace Core\Cache;

class CacheFactory
{
    public static function create($type, array $config)
    {
        switch ($type) {
            case 'file':
                return new FileCache($config);
                break;
            case 'apc':
                return new ApcCache($config);
                break;
            case 'redis':
                return new RedisCache($config);
                break;
            case 'memcached':
                return new MemCache($config);
                break;
            default:
                throw new CacheException(__METHOD__ . "(): invalid type '{$type}'");
        }
    }
}