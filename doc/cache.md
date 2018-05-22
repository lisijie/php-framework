# 缓存

支持以下缓存类型：
- File
- Apc
- Memcached
- Redis
- RedisCluster

## 配置

需要在 app.php 配置文件中增加以下cache配置项，如

```php
'cache' => [
    'default' => [
        'class' => \Core\Cache\NullCache::class,
        'config' => [],
    ],
    'node2' => [
        'class' => \Core\Cache\RedisCache::class,
        'config' => [
            'host' => 'localhost',
            'port' => 6379,
            'auth' => '123456',
        ],
    ],
],
```

其中 `default` 为默认节点配置，当不加参数调用 `App::cache()` 时，返回默认节点的缓存对象。 后面的 `node2` 为其他缓存节点的配置，在节点的配置中，`class` 为实现 Core\Cache\CacheInterface 接口的缓存驱动类名，可以自己扩展，`config` 则是驱动的配置信息。

下面列出各种缓存的配置选项：

**FileCache配置**

- save_path 缓存文件存放目录

**ApcCache配置**

无配置项，需要安装APC扩展：https://pecl.php.net/package/apcu

**MemCache配置**

需要安装memcached扩展：https://pecl.php.net/package/memcached

- servers 服务器列表，如
```php
    [
        ['192.168.1.1', 11211], // memcached服务器1
        ['192.168.1.2', 11211], // memcached服务器2
    ],
```
- options 可选。Memcached的一些配置项，为关联数组，其中键是要设置的选项，而值是选项的新值。例如：
```php
    [
        \Memcached::OPT_DISTRIBUTION => \Memcached::DISTRIBUTION_CONSISTENT,
        \Memcached::OPT_SERIALIZER => \Memcached::SERIALIZER_JSON,
    ]
```

**Redis配置**

需要安装redis c扩展：https://pecl.php.net/package/redis

- host Redis服务器IP，默认为127.0.0.1
- port 端口，默认为6379
- timeout 超时设置，默认0
- auth 授权密码
- options Redis的其他配置项，为关联数组。

**RedisCluster配置**

Redis 3.x支持的集群模式，需要确保你的Redis集群是使用这种方式搭建。

- servers 服务器列表，如：
```php
    ['192.168.1.1:6379','192.168.1.2:6379', '192.168.1.3:6379']
```
- options Redis的其他配置项，为关联数组。

## 使用

使用 `App::cache()` 方法获取一个缓存对象。

```php
$cache = \App::cache();
$cache->set('foo', 'bar');
echo $cache->get('foo');
```

要获取某个节点的对象，需要传入节点参数。

```php
$cache = \App::cache('node1');
```

支持的操作方法参照 `CacheInterface` 接口。
