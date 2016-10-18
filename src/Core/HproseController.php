<?php

namespace Core;

use Core\Http\Response;
use Core\Exception\AppException;
use Hprose\Http\Server as HproseServer;
use Hprose\Filter\JSONRPC\ServiceFilter;

class HproseController extends Controller
{

    /**
     * 执行当前控制器方法
     *
     * @param string $actionName 方法名
     * @param array $params 参数列表
     * @return Response|mixed
     * @throws AppException
     */
    public function runActionWithParams($actionName, $params = [])
    {
        $server = new HproseServer();
        $server->addFilter(new ServiceFilter());
        $methods = [];
        foreach (get_class_methods($this) as $method) {
            if (substr($method, -6) == 'Action') {
                $methods[$method] = substr($method, 0, -6);
            }
        }
        $server->addMethods(array_keys($methods), $this, array_values($methods));
        if (!\App::isProduction()) {
            $server->setDebugEnabled();
        }
        $server->setCrossDomainEnabled(false);
        $server->setP3PEnabled(false);
        $server->setGetEnabled(true);
        ob_start();
        $server->start();
        $this->response->setContent(ob_get_clean());
        return $this->response;
    }
}
