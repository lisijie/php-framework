<?php
namespace Core\Router;

use Core\Http\Request;

/**
 * 路由解析器接口定义
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Router
 */
interface RouterInterface
{
    // 设置路由配置表
    public function setConfig(array $config);

    // 设置默认路由地址
    public function setDefaultRoute($route);

    // 获取默认路由地址
    public function getDefaultRoute();

    // 解析请求
    public function resolve(Request $request);

    // 获取解析到的路由地址
    public function getRoute();

    // 获取解析到的路由参数
    public function getParams();

    // 根据路由地址和参数生成访问URL
    public function makeUrl($route, $params = []);
}
