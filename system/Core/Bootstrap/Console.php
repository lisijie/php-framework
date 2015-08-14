<?php
namespace Core\Bootstrap;

use Core\Router\Router;

/**
 * 控制台引导程序
 *
 * @package Core\Bootstrap
 */
class Console extends Bootstrap
{
	public function initRouter()
	{
        $options = array(
        	'type' => 'Console',
        	'default_route' => 'help/index', //默认路由
        );
        $router = Router::factory($options);
        return $router;
	}
}
