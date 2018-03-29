<?php
namespace Core\Cache;

/**
 * Redis v3 集群
 *
 * 配置：
 * $config = array(
 *     'prefix' => 键名前缀
 *     'servers' => ['192.168.1.1:6379','192.168.1.2:6379', '192.168.1.3:6379'],
 *     'options' => [],
 * )
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Cache
 */
class RedisClusterCache extends RedisCache
{
    protected function init()
    {
        if (!class_exists('\\RedisCluster')) {
            throw new CacheException("当前环境不支持RedisCluster");
        }
        $this->client = new \RedisCluster(null, $this->config['servers']);
        if (!empty($this->config['options'])) {
            foreach ($this->config['options'] as $name => $val) { // [\Redis::OPT_SERIALIZER => \Redis::SERIALIZER_PHP]
                $this->client->setOption($name, $val);
            }
        }
    }
}
