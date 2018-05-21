<?php
namespace Core\Container;

class ServiceProvider implements ServiceProviderInterface
{
    private $container;
    private $config = [];

    public function __construct(Container $container, array $config = [])
    {
        $this->container = $container;
        $this->config = $config;
    }

    public function get($name)
    {
        if (isset($this->config[$name])) {
            $this->container->set($name, $this->config[$name], true);
        }
        return $this->container->get($name);
    }

    public function has($name)
    {
        if (isset($this->config[$name])) {
            return true;
        }
        return false;
    }
}