<?php
namespace Core\Router;

/**
 * 用于命令行工具的路由
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Router
 */
class Console extends Router
{

    protected function parse()
    {
        $argv = $_SERVER['argv'];
        array_shift($argv);
        if (!empty($argv)) {
            $routeName = array_shift($argv);
            $this->routeName = $this->normalizeRoute($routeName);
        }
        $this->params = $argv;
    }

    public function makeUrl($route, $params = [])
    {
        $route = $this->normalizeRoute($route);
        return $route . ' ' . implode(' ', $params);
    }

}
