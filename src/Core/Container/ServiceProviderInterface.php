<?php
namespace Core\Container;

/**
 * 服务提供者接口
 *
 * @package Core\Container
 */
interface ServiceProviderInterface
{
    public function get($name, $args = []);

    public function has($name);
}