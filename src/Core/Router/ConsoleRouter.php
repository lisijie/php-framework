<?php
namespace Core\Router;

/**
 * 用于命令行工具的路由
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Router
 */
class ConsoleRouter extends AbstractRouter implements RouterInterface
{
    public function makeUrl($route, $params = [])
    {
        $route = $this->normalizeRoute($route);
        foreach ($params as $k => $v) {
            $params[$k] = escapeshellarg($v);
        }
        return $_SERVER['argv'][0] . ' '. $route . ' ' . implode(' ', $params);
    }

    /**
     * 解析
     * @param null $argv
     * @return mixed
     */
    public function resolve($argv = null)
    {
        if (null === $argv) {
            $argv = $_SERVER['argv'];
        }
        array_shift($argv);
        if (!empty($argv)) {
            $routeName = array_shift($argv);
            $this->routeName = $this->normalizeRoute($routeName);
        }
        $this->params = $argv;
        return $this->resolveHandler();
    }
}
