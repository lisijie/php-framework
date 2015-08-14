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

    public function parse()
    {
    	$argv = $_SERVER['argv'];
    	array_shift($argv);
    	if (!empty($argv)) {
    		$this->routeName = array_shift($argv);
    	}
    	$this->params = $argv;
    }

    public function makeUrl($route, $params = array())
    {
        $result = $this->makeUrlPath($route, $params);
        return $result['path'] . ' ' . implode(' ', $result['params']);
    }

}
