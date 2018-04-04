<?php
namespace Core\Router;

use Core\Exception\HttpNotFoundException;
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
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Router
 */
class HttpRouter extends AbstractRouter implements RouterInterface
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
    protected $handlerMap = [];

    /**
     * 路由查找表
     * @var array
     */
    protected $pathMap = [];

    /**
     * 请求对象
     * @var \Core\Http\Request
     */
    protected $request;

    /**
     * 路由变量
     * @var string
     */
    protected $routeVar = 'r';

    /**
     * 是否启用URL重写
     * @var bool
     */
    private $prettyUrl = false;

    public function __construct($options = [])
    {
        if (isset($options['default_route'])) {
            $this->defaultRoute = (string)$options['default_route'];
        }
        if (isset($options['route_var'])) {
            $this->routeVar = (string)$options['route_var'];
        }
        if (isset($options['pretty_url'])) {
            $this->prettyUrl = (bool)$options['pretty_url'];
        }
        if (!empty($options['namespaces'])) {
            foreach ($options['namespaces'] as $namespace) {
                $this->registerNamespace($namespace[0], $namespace[1]);
            }
        }
    }

    /**
     * 路由解析
     *
     * @param Request $request
     * @return array
     */
    public function resolve($request = null)
    {
        $this->request = $request;
        if (!$this->prettyUrl) {
            $route = $this->request->getQuery($this->routeVar);
        } else {
            $requestUri = $this->request->getRequestUri();
            $parts = parse_url($requestUri);
            $route = $parts['path'];
            // 去掉项目目录
            $baseUrl = $this->request->getBaseUrl();
            if ($baseUrl && ($pos = strpos($route, $baseUrl)) === 0) {
                $route = substr($route, strlen($baseUrl));
            }
        }
        $this->parseRoute(trim($route, '/'));
        return $this->resolveHandler();
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
     * @param string $pattern 路径规则
     * @param string $handler 处理器
     * @param string $method 允许的请求方法
     */
    public function addRoute($pattern, $handler, $method = self::METHOD_ANY)
    {
        if (is_string($handler)) {
            $handler = $this->normalizeRoute($handler);
        }
        // 必须以"/"开头
        if ($pattern[0] != '/') {
            $pattern = '/' . $pattern;
        }
        // 匹配类型
        if (strpos($pattern, '*') !== false) {
            $type = self::TYPE_ANY;
        } elseif (strpos($pattern, ':') !== false) {
            $type = self::TYPE_PARAM;
        } else {
            $type = self::TYPE_STATIC;
        }
        $parts = explode('/', $pattern);
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
        $this->pathMap[$type][$re] = ['handler' => $handler, 'method' => $method];
        if (is_string($handler)) {
            $this->handlerMap[$handler] = ['pattern' => $pattern];
        }
    }

    /**
     * 路由解析
     *
     * 将路径解析为对应的路由地址和参数。
     *
     * @param string $path
     * @return bool
     * @throws HttpNotFoundException
     * @throws MethodNotAllowedException
     */
    private function parseRoute($path)
    {
        if (empty($path)) return false;

        // 包含非法字符则抛出404异常
        if (!preg_match('#^[a-z][a-z0-9/\-]+$#i', $path)) {
            throw new HttpNotFoundException();
        }
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
                        $reqMethod = $this->request->getMethod();
                        if (!in_array($reqMethod, explode(',', $item['method']))) {
                            throw new MethodNotAllowedException();
                        }
                    }
                    $this->routeName = $item['handler'];
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
     * @param string $handlerName
     * @param array $params
     * @return array('path'=>路径, 'params'=>参数)
     */
    protected function makeUrlPath($handlerName, array $params)
    {
        $handlerName = $this->normalizeRoute($handlerName);
        $path = '';
        if (isset($this->handlerMap[$handlerName])) {
            $pattern = $this->handlerMap[$handlerName]['pattern'];
            $parts = explode('/', $pattern);
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
            $path = $handlerName;
        }
        return ['path' => $path, 'params' => $params];
    }

    /**
     * 生成URL
     *
     * @param string $route 路由地址
     * @param array $params 参数
     * @return string
     */
    public function makeUrl($route, $params = [])
    {
        $result = $this->makeUrlPath($route, $params);
        if (!$this->prettyUrl) {
            $query = $result['params'];
            $query[$this->routeVar] = $result['path'];
            return $this->request->getBaseUrl() . '/?' . http_build_query($query);
        } else {
            return $this->request->getBaseUrl() . '/' . ltrim($result['path'], '/') . (empty($result['params']) ? '' : '?' . http_build_query($result['params']));
        }
    }
}
