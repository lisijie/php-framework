<?php

namespace Core\Router;

use Core\Http\Request;

/**
 * 路由解析器
 *
 * 路由解析器提供 URI 到控制器方法的转换，配置说明：
 *  - 每条路由配置都是一个数组，格式为 array('URI规则', '对应的控制器方法', array(附加参数))
 *  - URI规则中的变量由大括号包含，格式为 {var:type} ，var 为变量名称，type 为匹配类型，可以是内置的类型(int,str,date,year)，或正则表达式,
 *    类型部分可以省略，默认为匹配字符串，例如 {name}
 *  - 路由对应的控制器方法根据控制器的类名转换而来，格式为: 类名/方法名，省略Controller和Action，例如： Admin/User/UserList 表示 App\Controller\Admin\UserController::UserListAction() 
 *    你也可以使用全小写的方式 admin/user/user-list，不管用哪种，最终都统一转换为全小写的地址。
 *  - 如果配置了附加参数，路由规则匹配后使用 getParams() 方法获取到的参数列表将包含路由参数和附加参数，这项配置是可选的，没有的话可以省略
 *
 * 示例：
 * array(
 *     array('list/{cat_id:int}/{page:int}', 'Main/Article/List'),
 *     array('article/{id:int}', 'Main/Article/Show'),
 *     array('login', 'Main/User/Login'),
 *     array('register', 'Main/User/Register'),
 *     array('users', 'Main/User/UserList'),
 * );
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Router
 */
abstract class Router
{
    protected $config = array();

    /**
     * 控制器到URI规则的映射
     * @var array
     */
    protected $routeMap = array();

    /**
     * URI规则到控制器的映射
     * @var array
     */
    protected $uriMap = array();

    /**
     * 变量对应正则
     *
     * @var array
     */
    protected $typeRegexp = array(
        'int' => '(\d+)',
        'string' => '([^/\#]*)',
        'str' => '([^/\#]*)',
        'date' => '(\d{8})',
        'year' => '(\d{4})',
    );

    /**
     * 当前路由参数
     *
     * @var array
     */
    protected $params = array();

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
     * 请求对象
     *
     * @var \Core\Http\Request
     */
    protected $request;

    /**
     * 路由变量
     *
     * @var string
     */
    protected $routeVar = 'r';

    public function __construct($options = array())
    {
        if (isset($options['default_route'])) {
            $this->defaultRoute = (string)$options['default_route'];
        }
        if (isset($options['route_var'])) {
            $this->routeVar = (string)$options['route_var'];
        }
    }

    /**
     * 设置路由配置
     * @param array $config
     */
    public function setConfig(array $config)
    {
        $this->config = $config;
        $this->parseConfig();
    }

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
     * 标准化路由地址
     *
     * 全部转成小写，每个单词用"-"分隔，例如 Admin/UserList 转换为 admin/user-list
     *
     * @param $route
     * @return string
     */
    public function normalizeRoute($route)
    {
        $route = preg_replace_callback('#[A-Z]#', function($m) {
            return '-' . strtolower($m[0]);
        }, $route);
        return ltrim(strtr($route, array('/-' => '/')), '-');
    }

    /**
     * 解析配置
     */
    protected function parseConfig()
    {
        foreach ($this->config as $conf) {
            list($url, $route) = $conf;
            $route = $this->normalizeRoute($route);
            $params = array();
            if (strpos($url, '{') !== false) {
                $re = preg_replace_callback('#{[^}]+}#', function ($matches) use (&$params) {
                    $string = trim($matches[0], '{}');
                    if (strpos($string, ':') !== false) {
                        list($name, $rule) = explode(':', $string);
                    } else {
                        $name = $string;
                        $rule = 'str';
                    }
                    $params[] = $name;
                    return isset($this->typeRegexp[$rule]) ? $this->typeRegexp[$rule] : $rule;
                }, $url);
            } else {
                $re = $url;
            }
            $this->uriMap[$re] = array('route' => $route, 'params' => (isset($conf[2])&&is_array($conf[2]) ? array_merge($params, $conf[2]) : $params));
            $this->routeMap[$route][] = array('url' => $url, 'params' => array_flip($params));
        }
    }

    /**
     * 解析URL
     *
     * 将URL解析为对应的路由地址和参数。
     *
     * @param string $url
     * @return bool
     */
    protected function parseUrl($url)
    {
        if (empty($url)) return false;
        $match = false;
        foreach ($this->uriMap as $re => $value) {
            if (preg_match('#^' . $re . '$#i', $url, $matches)) {
                $routeParams = array();
                foreach ($value['params'] as $k => $v) {
                    if (is_int($k) && isset($matches[$k+1])) {
                        $routeParams[$v] = $matches[$k+1];
                    } else {
                        $routeParams[$k] = $v;
                    }
                }
                $this->routeName = $value['route'];
                $this->params = $routeParams;
                $match = true;
                break;
            }
        }
        if (!$match) {
            $this->routeName = trim($url, '/');
        }
    }

    /**
     * 根据规则生成URL路径部分
     *
     * @param string $route
     * @param array $params
     * @return array('path'=>路径, 'params'=>参数)
     */
    protected function makeUrlPath($route, $params)
    {
        $route = $this->normalizeRoute($route);
        $path = '';
        if (isset($this->routeMap[$route])) {
            $map = array();
            $n = -1;
            foreach ($this->routeMap[$route] as $value) {
                //参数完全匹配
                if (count($value['params']) == count($params) && !array_diff_key($value['params'], $params)) {
                    $map = $value;
                    break;
                }
                //寻找最佳匹配
                $count = count(array_intersect_key($value['params'], $params)); // 相同参数数量
                if ($count >= count($value['params']) && $count > $n) {
                    $map = $value;
                    $n = $count;
                }
            }
            if ($map) {
                $path = preg_replace_callback('#{[^}]+}#', function ($matches) use (&$params) {
                    $name = trim($matches[0], '{}');
                    if (strpos($name, ':') !== false) {
                        list($name) = explode(':', $name);
                    }
                    if (isset($params[$name])) {
                        $v = $params[$name];
                        unset($params[$name]);
                        return rawurlencode($v);
                    }
                    return '';
                }, $map['url']);
            }
        }
        if (!$path) {
            $path = $route;
        }

        return array('path' => $path, 'params' => $params);
    }

    /**
     * 获取路由地址
     */
    public function getRoute()
    {
        return $this->routeName ? : $this->getDefaultRoute();
    }

    /**
     * 获取路由参数列表
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * 获取请求的主机名
     */
    public function getHost()
    {
        return $this->request->getHostName();
    }

    public function resolve(Request $request)
    {
        $this->request = $request;
        $this->parse();
    }

    /**
     * 生成URL
     *
     * @param string $route 路由地址
     * @param array $params 参数
     * @return string
     */
    abstract public function makeUrl($route, $params = array());

    /**
     * 开始路由解析
     */
    abstract public function parse();

    /**
     * 工厂方法
     * 实例化指定类型路由器
     *
     * @param array $options
     * @return \Core\Router\Router
     * @throws \InvalidArgumentException
     */
    public static function factory(array $options)
    {
        $className = '\\Core\\Router\\' . ucfirst($options['type']);
        if (class_exists($className) && is_subclass_of($className, '\\Core\\Router\Router')) {
            return new $className($options);
        }
        throw new \InvalidArgumentException("Unknown Router : {$options['type']}");
    }
}
