<?php
/**
 * PATH_INFO理由解析
 * 对$_SERVER['PATH_INFO']进行理由解析，需要WEB服务器支持
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Router
 */

namespace Core\Router;

class Pathinfo extends Router
{

    public function parse()
    {
        if (null !== ($pathInfo = $this->request->getPathInfo())) {
            $this->parseUrl($pathInfo);
        }
    }

    public function makeUrl($route, $params = array())
    {
        $result = $this->makeUrlPath($route, $params);
        return $this->request->getBaseUrl() . '/' . $result['path'] . (empty($result['params']) ? '' : '&' . http_build_query($result['params']));
    }

}
