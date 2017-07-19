<?php
namespace Core\Router;

use Core\Http\Request;

/**
 * 路由解析器
 *
 * 路由解析器提供 URI 到控制器方法的转换，配置说明：
 *  - 每条路由配置都是一个数组，格式为 ['URI规则', '对应的控制器方法', 'HTTP方法']
 *  - URI规则中的可以使用变量或者通配符，变量语法是`:var`，如: /user/:id，使用通配符如：/home/*。
 *  - 路由对应的控制器方法根据控制器的类名转换而来，格式为: 类名/方法名，省略Controller和Action，例如： Admin/User/UserList 表示 App\Controller\Admin\UserController::UserListAction()
 *    你也可以使用全小写的方式 admin/user/user-list，不管用哪种，最终都统一转换为全小写的地址。
 *
 * 示例：
 * [
 *     ['/', 'Home/index'],
 *     ['/article/:id', 'Article/Show'],
 *     ['/login', 'User/Login'],
 *     ['/register', 'user/register', 'POST'],
 *     ['/users/*', 'User/UserList', 'GET'],
 * ];
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Router
 */
abstract class Router implements RouterInterface
{
    /**
     * 固定路径
     */
    const TYPE_STATIC = 1;

    /**
     * 参数匹配
     */
    const TYPE_PARAM = 2;

    /**
     * 包含通配符
     */
    const TYPE_ANY = 3;

    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_HEAD = 'HEAD';
    const METHOD_OPTIONS = 'OPTIONS';
    const METHOD_PATCH = 'PATCH';
    const METHOD_DELETE = 'DELETE';
    const METHOD_ANY = '*';

    /**
     * 控制器到URI规则的映射
     * @var array
     */
    protected $routeMap = [];

    /**
     * 路由查找表
     * @var array
     */
    protected $pathMap = [];

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
     * 请求对象
     *
     * @var \Core\Http\Request
     */
    protected $request;

    /**
     * 默认路由
     *
     * @var string
     */
    protected $defaultRoute = '';

    /**
     * 路由变量
     *
     * @var string
     */
    protected $routeVar = 'r';

    public function __construct($options = [])
    {
        if (isset($options['default_route'])) {
            $this->defaultRoute = (string)$options['default_route'];
        }
        if (isset($options['route_var'])) {
            $this->routeVar = (string)$options['route_var'];
        }
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
     * 获取路由参数
     * @param $key
     * @return mixed|null
     */
    public function getParam($key)
    {
        return isset($this->params[$key]) ? $this->params[$key] : null;
    }

    /**
     * 路由解析
     * @param Request $request
     */
    public function resolve(Request $request)
    {
        $this->request = $request;
        return $this->parse();
    }

    /**
     * 添加路由配置
     *
     * @param array $config
     */
    public function addConfig(array $config)
    {
        foreach ($config as $key => $value) {
            if (is_string($key)) { // 组路由
                foreach ((array)$value as $item) {
                    $this->addRoute($key . $item[0], $item[1], isset($item[2]) ? strtoupper($item[2]) : self::METHOD_ANY);
                }
            } else {
                $this->addRoute($value[0], $value[1], isset($value[2]) ? strtoupper($value[2]) : self::METHOD_ANY);
            }
        }
    }

    /**
     * 添加路由规则
     *
     * @param string $path 路径规则
     * @param string $route 控制器地址
     * @param string $method 允许的请求方法
     */
    public function addRoute($path, $route, $method = self::METHOD_ANY)
    {
        $route = $this->normalizeRoute($route);
        // 必须以"/"开头
        if ($path[0] != '/') {
            $path = '/' . $path;
        }
        // 匹配类型
        if (strpos($path, '*') !== false) {
            $type = self::TYPE_ANY;
        } elseif (strpos($path, ':') !== false) {
            $type = self::TYPE_PARAM;
        } else {
            $type = self::TYPE_STATIC;
        }
        $parts = explode('/', $path);
        $pk = 0;
        foreach ($parts as $key => $part) {
            if (empty($part)) {
                continue;
            }
            if ($part[0] == ':') {
                $parts[$key] = '(?<' . substr($part, 1) . '>[^/]+?)'; // 换成正则
            } elseif ($part == '*') {
                $parts[$key] = '(?<idx' . $pk . '>.*?)';
                $pk++;
            }
        }
        $re = implode('/', $parts);
        if (!isset($this->pathMap[$type])) {
            $this->pathMap[$type] = [];
        }
        $this->pathMap[$type][$re] = ['route' => $route, 'method' => $method];
        $this->routeMap[$route] = ['path' => $path];
    }

    /**
     * 路由解析
     *
     * 将路径解析为对应的路由地址和参数。
     *
     * @param string $path
     * @return bool
     * @throws MethodNotAllowedException
     */
    public function parseRoute($path)
    {
        if (empty($path)) return false;
        if ($path[0] != '/') {
            $path = '/' . $path;
        }
        foreach ([self::TYPE_STATIC, self::TYPE_PARAM, self::TYPE_ANY] as $type) {
            if (!isset($this->pathMap[$type])) {
                continue;
            }
            foreach ($this->pathMap[$type] as $re => $item) {
                if (preg_match('#^' . $re . '$#i', $path, $matches)) {
                    // 检查请求方法是否匹配
                    if ($item['method'] != self::METHOD_ANY) {
                        $ok = false;
                        $reqMethod = $this->request->getMethod();
                        foreach (explode(',', $item['method']) as $method) {
                            if ($method == $reqMethod) {
                                $ok = true;
                                break;
                            }
                        }
                        if (!$ok) {
                            throw new MethodNotAllowedException();
                        }
                    }
                    $this->routeName = $item['route'];
                    foreach ($matches as $k => $v) {
                        if (is_numeric($k)) {
                            continue;
                        }
                        if (substr($k, 0, 3) == 'idx') {
                            $this->params[substr($k, 3)] = $v;
                        } else {
                            $this->params[$k] = $v;
                        }
                    }
                    $this->request->addParams($this->params);
                    return true;
                }
            }
        }
        $this->routeName = trim($path, '/');
        return false;
    }

    /**
     * 根据规则生成URL路径部分
     *
     * @param string $route
     * @param array $params
     * @return array('path'=>路径, 'params'=>参数)
     */
    protected function makeUrlPath($route, array $params)
    {
        $route = $this->normalizeRoute($route);
        $path = '';
        if (isset($this->routeMap[$route])) {
            $path = $this->routeMap[$route]['path'];
            $parts = explode('/', $path);
            $pk = 0;
            foreach ($parts as $key => $val) {
                if (empty($val)) {
                    continue;
                }
                if ($val == '*') {
                    $parts[$key] = isset($params[$pk]) ? rawurlencode($params[$pk]) : '';
                    $pk++;
                    unset($params[$pk]);
                } elseif ($val[0] == ':') {
                    $pName = substr($val, 1);
                    $parts[$key] = isset($params[$pName]) ? rawurlencode($params[$pName]) : '';
                    unset($params[$pName]);
                }
            }
            $path = implode('/', $parts);
        }
        if (!$path) {
            $path = $route;
        }
        return ['path' => $path, 'params' => $params];
    }

    /**
     * 标准化路由地址
     *
     * 全部转成小写，每个单词用"-"分隔，例如 Admin/UserList 转换为 admin/user-list
     *
     * @param $route
     * @return string
     */
    private function normalizeRoute($route)
    {
        $route = preg_replace_callback('#[A-Z]#', function ($m) {
            return '-' . strtolower($m[0]);
        }, $route);
        return ltrim(strtr($route, ['/-' => '/']), '-');
    }

    /**
     * 生成URL
     *
     * @param string $route 路由地址
     * @param array $params 参数
     * @return string
     */
    abstract public function makeUrl($route, $params = []);

    /**
     * 开始路由解析
     */
    abstract protected function parse();

    /**
     * 工厂方法
     * 实例化指定类型路由器
     *
     * @param $type
     * @param array $options
     * @return Router
     */
    public static function factory($type, array $options = [])
    {
        $className = '\\Core\\Router\\' . ucfirst($type);
        if (class_exists($className) && is_subclass_of($className, '\\Core\\Router\Router')) {
            return new $className($options);
        }
        throw new \InvalidArgumentException("Unknown Router : {$type}");
    }
}
