<?php
namespace Core\Router;

interface RouterInterface
{
    /**
     * 设置默认路由
     * @param string $route
     */
    public function setDefaultRoute($route);

    /**
     * 获取默认路由地址
     * @return string
     */
    public function getDefaultRoute();

    /**
     * 获取当前的路由地址
     * @return mixed
     */
    public function getRoute();

    /**
     * 获取当前的路由参数
     * @return mixed
     */
    public function getParams();

    /**
     * 注册查找命名空间前缀
     *
     * @param string $namespace 命名空间前缀
     * @param string $classSuffix 类名后缀
     */
    public function registerNamespace($namespace, $classSuffix);

    /**
     * 返回查找命名空间
     *
     * @return array
     */
    public function getNamespaces();

    /**
     * 解析
     * @param null $request
     * @return mixed
     */
    public function resolve($request = null);

    /**
     * 标准化路由地址
     *
     * 全部转成小写，每个单词用"-"分隔，例如 Admin/UserList 转换为 admin/user-list
     *
     * @param $route
     * @return string
     */
    public function normalizeRoute($route);

    /**
     * 生成URL
     *
     * @param string $route
     * @param array $params
     * @return mixed
     */
    public function makeUrl($route, $params = []);
}