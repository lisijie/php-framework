<?php

class RouteTest extends Core\TestCase
{
    public function testMakeUrl()
    {
        $request = new \Core\Http\Request();
        $router = \Core\Router\Router::factory(array('type'=>'rewrite'));
        $router->setConfig(array(
            array('test/{year:year}', 'Test/List', array('extra'=>'test')),
            array('test/{year:year}/{page:int}', 'Test/List', array('extra'=>'test')),
        ));
        $router->resolve($request);

        $baseUrl = $request->getBaseUrl();

        $this->assertTrue("{$baseUrl}/test/2015" == $router->makeUrl('Test/List', array('year'=>'2015')));
        $this->assertTrue("{$baseUrl}/test/2015/2" == $router->makeUrl('Test/List', array('year'=>'2015', 'page'=>2)));
        $this->assertTrue("{$baseUrl}/test/2015?foo=bar" == $router->makeUrl('Test/List', array('year'=>'2015', 'foo'=>'bar')));
    }

    public function testNormalize()
    {
        $router = \Core\Router\Router::factory(array('type'=>'rewrite'));

        $this->assertTrue($router->normalizeRoute('One/Two/ThreeFour') == 'one/two/three-four');
    }
}