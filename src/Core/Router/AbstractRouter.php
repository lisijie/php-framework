<?php
namespace Core\Router;

/**
 * 路由解析器
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Router
 */
class AbstractRouter
{
    /**
     * 当前路由参数
     *
     * @var array
     */
    protected $params = [];

    /**
     * 当前路由地址
     *
     * @var string
     */
    protected $routeName;

    /**
     * 默认路由
     *
     * @var string
     */
    protected $defaultRoute = '';


    /**
     * 查找的命名空间
     *
     * @var array
     */
    protected $findNamespaces = [];

    /**
     * 设置默认路由
     * @param $route
     */
    public function setDefaultRoute($route)
    {
        $this->defaultRoute = $route;
    }

    /**
     * 返回默认路由
     * @return string
     */
    public function getDefaultRoute()
    {
        return $this->defaultRoute;
    }

    /**
     * 获取路由地址
     * @return string
     */
    public function getRoute()
    {
        return $this->routeName ?: $this->defaultRoute;
    }

    /**
     * 获取路由参数列表
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * 标准化路由地址
     *
     * 全部转成小写，每个单词用"-"分隔，例如 Admin/UserList 转换为 admin/user-list
     *
     * @param $route
     * @return string
     */
    protected function normalizeRoute($route)
    {
        $route = preg_replace_callback('#[A-Z]#', function ($m) {
            return '-' . strtolower($m[0]);
        }, $route);
        return ltrim(strtr($route, ['/-' => '/']), '-');
    }

    /**
     * 注册查找命名空间前缀
     *
     * @param string $namespace 命名空间前缀
     * @param string $classSuffix 类名后缀
     */
    public function registerNamespace($namespace, $classSuffix)
    {
        $this->findNamespaces[] = [$namespace, $classSuffix];
    }

    /**
     * 返回查找命名空间
     *
     * @return array
     */
    public function getNamespaces()
    {
        return $this->findNamespaces;
    }

    /**
     * 解析路由地址返回控制器名和方法名
     *
     * 路由地址由英文字母、斜杠和减号组成，如：/foo/bar/say-hello。
     * 解析步骤如下：
     * 1. 首先将路由地址转换为 Foo\Bar\SayHello。
     * 2. 则将路由地址分割为两部分，Foo\Bar 为控制器名，SayHello 为方法名。检查是否存在
     *    名为 Foo\BarController 的控制器，如果存在，则解析成功。
     * 3. 如果控制器不存在，检查是否存在名为 Foo\Bar\SayHelloController 的控制器是否存在，存在则解析完成。
     * 4. 如果控制器不存在，则返回的控制器名称为空。
     *
     * @return array 返回 [控制器名称, 方法名]
     */
    protected function resolveHandler()
    {
        $route = $this->getRoute();
        $pos = strrpos($route, '/');
        if ($pos !== false) {
            // 转换成首字母大写+反斜杠形式
            $route = str_replace(' ', '\\', ucwords(str_replace('/', ' ', $route)));
        } else {
            $route = ucfirst($route);
        }
        // 将减号分割的单词转换为首字母大写的驼峰形式
        if (strpos($route, '-') !== false && strpos($route, '--') === false) {
            $route = str_replace(' ', '', ucwords(str_replace('-', ' ', $route)));
        }
        $namespaces = $this->findNamespaces;
        $controllerName = $actionName = '';
        if ($pos > 0) {
            $pos = strrpos($route, '\\');
            $tmpControl = substr($route, 0, $pos);
            foreach ($namespaces as $item) {
                list($nsPrefix, $classSuffix) = $item;
                $class = "{$nsPrefix}\\{$tmpControl}{$classSuffix}";
                if (class_exists($class)) {
                    $controllerName = $class;
                    $actionName = lcfirst(substr($route, $pos + 1));
                    break;
                }
            }
        }
        if (!$controllerName) {
            foreach ($namespaces as $item) {
                list($nsPrefix, $classSuffix) = $item;
                $class = "{$nsPrefix}\\{$route}{$classSuffix}";
                if (class_exists($class)) {
                    $controllerName = $class;
                    break;
                }
            }
        }

        return [$controllerName, $actionName];
    }
}
