<?php

namespace Core\Router;

/**
 * 简单路由解析
 * 根据QueryString解析
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Router
 */
class Simple extends Router
{

    protected function parse()
    {
        $r = $this->request->getQuery($this->routeVar);
        if (!empty($r)) {
            $this->parseRoute($r);
        }
    }

    public function makeUrl($route, $params = [])
    {
        $result = $this->makeUrlPath($route, $params);
        return $this->request->getBaseUrl() . '/?' . $this->routeVar . '=' . $result['path'] . (empty($result['params']) ? '' : '&' . http_build_query($result['params']));
    }

}
