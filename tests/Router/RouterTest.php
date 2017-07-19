<?php

class RouterTest extends TestCase
{

    public function testParse()
    {
        $config = [
            ['/', 'home/index'],
            ['/users', 'user/list', 'get'],
            ['/user/:id', 'user/info', 'get'],
            ['/register', 'user/register', 'post'],
        ];
        $router = Core\Router\Router::factory('rewrite');
        $router->addConfig($config);
        $request = new \Core\Http\Request();

        $request->setRequestUri('/');
        $router->resolve($request);
        $this->assertTrue($router->getRoute() == 'home/index');

        $request->setRequestUri('/users');
        $router->resolve($request);
        $this->assertTrue($router->getRoute() == 'user/list');

        $request->setRequestUri('/user/123');
        $router->resolve($request);
        $this->assertTrue($router->getRoute() == 'user/info');
        $this->assertTrue($router->getParam('id') == 123);
    }

    /**
     * @expectedException \Core\Router\MethodNotAllowedException
     */
    public function testNotAllowedRoute()
    {
        $config = [
            ['/register', 'user/register', 'post'],
        ];
        $router = Core\Router\Router::factory('rewrite');
        $router->addConfig($config);
        $request = new \Core\Http\Request();
        $request->setRequestUri('/register');
        $router->resolve($request);
    }

    public function testNotExistsRoute()
    {
        $config = [];
        $router = Core\Router\Router::factory('rewrite', ['default_route' => 'home/index']);
        $router->addConfig($config);
        $request = new \Core\Http\Request();
        $request->setRequestUri('/not/exists/path');
        $router->resolve($request);
        $this->assertTrue($router->getRoute() == 'not/exists/path');

        // default
        $request->setRequestUri('/');
        $router->resolve($request);
        $this->assertTrue($router->getRoute() == 'home/index');
    }
}