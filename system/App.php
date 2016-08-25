<?php
/**
 * 框架引导程序
 *
 * @author lisijie <lsj86@qq.com>
 */

//检查PHP版本，必须5.4以上
if (version_compare(PHP_VERSION, '5.4.0', '<')) {
    die('require PHP > 5.4.0 !');
}

//检查目录常量
foreach (array('APP_PATH', 'DATA_PATH') as $name) {
    if (!defined($name)) {
        header('Content-Type:text/html; charset=UTF-8;');
        die("常量 [{$name}] 未定义！");
    }
}

//设置错误报告级别, 使用最严格的标准
error_reporting(E_ALL | E_STRICT);

//系统常量定义
require __DIR__ . '/Const.php';
//自动加载类
require __DIR__ . '/ClassLoader.php';
//加载公共函数库
require __DIR__ . '/Core/Common.php';
//加载composer自动加载类
if (is_file(VENDOR_PATH . 'autoload.php')) {
    require VENDOR_PATH . 'autoload.php';
}

//关闭显示错误消息, 所有错误已经转换成异常, 并注册了默认异常处理器
ini_set('display_errors', DEBUG);

//注册自动加载
ClassLoader::getInstance()
	->registerNamespace('Core', __DIR__ . '/Core')
	->registerNamespace('App', rtrim(APP_PATH, DIRECTORY_SEPARATOR))
	->register();

use Core\Http\Request;
use Core\Http\Response;
use Core\Exception\HttpNotFoundException;
use Core\Bootstrap\BootstrapInterface;
use Core\Container;
use Core\Exception\HttpException;
use Core\Object;
use Core\Environment;

class App extends Object
{

    /**
     * 容器
     *
     * @var Container;
     */
    protected static $container;

    /**
     * 控制器命名空间
     * 
     * @var string|array
     */
    protected static $controllerNamespace = 'App\\Controller';

    /**
     * 运行应用并输出结果
     */
    public static function run()
    {
        if (self::isCli()) {
            self::$controllerNamespace = array('App\\Command', 'Core\\Console\\Controller');
        }
        $config = self::config()->get('app', 'profiler', []);
        if ($config && $config['enabled']) {
            $profiler = new \Core\Lib\Profiler();
            $profiler->setDataPath($config['data_path']);
            $profiler->setXhprofUrl($config['xhprof_url']);
            $profiler->start();
        }
        $request = new Request();
        $response = self::handleRequest($request);
        $response->send();
    }

    /**
     * 设置控制器命名空间前缀
     *
     * @param $ns
     */
    public static function setControllerNamespace($ns) {
        self::$controllerNamespace = $ns;
    }

    /**
     * 是否命令行模式
     * 
     * @return bool
     */
    public static function isCli()
    {
        return PHP_SAPI == 'cli';
    }

    /**
     * 处理请求
     * 
     * @param  Request $request
     * @return Response 返回response对象
     */
    public static function handleRequest(Request $request)
    {
        $router = self::router();
        $router->resolve($request);
        //当前路由地址
        define('CUR_ROUTE', $router->getRoute());
        $request->addParams($router->getParams());
        static::set('request', $request);

        return self::runRoute(CUR_ROUTE, $router->getParams());
    }

    /**
     * 获取控制目录
     *
     * @return array
     */
    public static function getControllerPaths()
    {
        $paths = [];
        foreach ((array)static::$controllerNamespace as $ns) {
            $ps = ClassLoader::getInstance()->getNamespacePaths(strstr($ns, '\\', true));
            foreach ($ps as &$v) {
                $v .= strtr(strstr($ns, '\\'), '\\', DS);
            }
            $paths[$ns] = $ps;
        }
        return $paths;
    }

    /**
     * 执行路由
     *
     * @param string $route 路由地址
     * @param array $params 路由参数
     * @throws HttpNotFoundException
     * @return Response
     */
    public static function runRoute($route, $params = [])
    {
        if (!preg_match('#^[a-z][a-z0-9/\-]+$#i', $route)) {
            throw new HttpNotFoundException();
        }

        list($controllerName, $actionName) = static::parseRoute($route);

        if (!class_exists($controllerName)) {
            throw new HttpNotFoundException();
        }

        $response = new Response();
        self::set('response', $response);

        try {
            $controller = new $controllerName(self::get('request'), $response);
            $controller->init();
	        self::set('controller', $controller);
            if ($controller->before() === true) {
                $response = $controller->runActionWithParams($actionName, $params);
                $controller->after();
            }
            return $response;
        } catch (BadMethodCallException $e) {
            throw new HttpNotFoundException();
        }
        
    }

    /**
     * 解析路由地址返回控制器名和方法名
     *
     * 路由地址由英文字母、斜杠和减号组成，如：/foo/bar/say-hello
     * 将解析到 Foo/BarController 控制器的 sayHelloAction() 方法。
     *
     * @param string $route 路由地址
     * @return array 返回 [控制器名称, 方法名]
     */
    public static function parseRoute($route)
    {
        if (($pos = strrpos($route, '/')) !== false) {
            $value = str_replace('/', ' ', substr($route, 0, $pos));
            $value = str_replace(' ', '\\', ucwords($value));
        } else {
            $value = ucfirst($route);
        }
        if (strpos($value, '-') !== false && strpos($value, '--') === false) {
            $value = str_replace(' ', '', ucwords(str_replace('-', ' ', $value)));
        }
	    $controllerName = $actionName = '';
        if (is_array(static::$controllerNamespace)) {
            foreach (static::$controllerNamespace as $ns) {
                $controllerName = $ns . "\\{$value}Controller";
                if (class_exists($controllerName)) {
                    break;
                }
            }
        } else {
            $controllerName = static::$controllerNamespace . "\\{$value}Controller";
        }
        if ($pos) {
            $actionName = substr($route, strrpos($route, '/') + 1);
            if (strpos($actionName, '-') !== false && strpos($actionName, '--') === false) {
                $actionName = ucwords(str_replace('-', ' ', $actionName));
	            $actionName = lcfirst(str_replace(' ', '', $actionName));
            }
            $actionName = $actionName . 'Action';
        }

        return [$controllerName, $actionName];
    }

    /**
     * 执行引导程序
     *
     * 先调用所有init开头的方法，最后调用startup方法初始化
     *
     * @param BootstrapInterface $bootstrap
     */
    public static function bootstrap(BootstrapInterface $bootstrap = null)
    {
        self::$container = new Container();
        if (!is_object($bootstrap)) {
            if (static::isCli()) {
                $bootstrap = new \Core\Bootstrap\Console();
            } else {
                $bootstrap = new \Core\Bootstrap\Bootstrap();
            }
        }
	    self::$container->setSingleton('config', new \Core\Config(CONFIG_PATH, Environment::getEnvironment()));

        $class = new ReflectionClass($bootstrap);
        $methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            $methodName = $method->getName();
            if (substr($methodName, 0, 4) == 'init') {
                self::$container->set(lcfirst(substr($methodName, 4)), [$bootstrap, $methodName], true);
            }
        }
        $bootstrap->startup();
    }

    /**
     * 语言包解析
     *
     * 如果$langId不包含点号，则从公共语言包 language.php 文件搜索对应索引，如果
     * 公共语言包文件不存在，则直接返回 $langId。
     *
     * @param string $langId 语言ID,格式：文件名.数组key
     * @param array $params
     * @throws InvalidArgumentException
     * @return string
     */
    public static function lang($langId, $params = [])
    {
        static $cache = [];
        if (false === strpos($langId, '.')) {
            if (!isset($cache['common'])) {
                $filename = App::config()->get('app', 'lang', 'zh_CN') . "/language.php";
                if (is_file(LANG_PATH . $filename)) {
                    $lang = [];
                    include LANG_PATH . $filename;
                    $cache['common'] = $lang;
                }
            }
            if (isset($cache['common'][$langId])) {
                $file = 'common';
                $idx = $langId;
            } else {
                return $langId;
            }
        } else {
            list($file, $idx) = explode('.', $langId);
            if ($file && !isset($cache[$file])) {
                $lang = [];
                $filename = App::config()->get('app', 'lang', 'zh_CN') . "/{$file}.php";
                if (!is_file(LANG_PATH . $filename)) {
                    throw new InvalidArgumentException("lang file {$filename} not exists.");
                }
                include LANG_PATH . $filename;
                $cache[$file] = $lang;
            }
            if (!isset($cache[$file][$idx])) {
                throw new InvalidArgumentException("lang {$langId} not exists.");
            }
        }
        return preg_replace_callback('/{\$(\d+)}/', function ($m) use (&$params) {
            return $params[$m[1] - 1];
        }, $cache[$file][$idx]);
    }

    /**
     * 抛出一个HTTP异常
     *
     * @param $code
     * @param string $message
     * @throws HttpException
     */
    public static function abort($code, $message = '')
    {
        throw new HttpException($code, $message);
    }

    /**
     * 获取DB实例
     *
     * @param string $name 节点名称
     * @return Core\Db
     */
    public static function db($name = 'default')
    {
        return self::get('db', $name);
    }

    /**
     * 返回Request对象
     *
     * @return Core\Http\Request
     */
    public static function request()
    {
        return self::get('request');
    }

    /**
     * 返回Response对象
     *
     * @return Core\Http\Response
     */
    public static function response()
    {
        return self::get('response');
    }

    /**
     * 返回路由对象
     *
     * @return Core\Router\Router
     */
    public static function router()
    {
        return self::get('router');
    }

    /**
     * 返回Session对象
     *
     * @return Core\Session\Session
     */
    public static function session()
    {
        return self::get('session');
    }

    /**
     * 返回缓存对象
     *
     * @param string $name 节点名称
     * @return Core\Cache\CacheInterface
     */
    public static function cache($name = 'default')
    {
        return self::get('cache', $name);
    }

    /**
     * 返回日志对象
     *
     * @param string $name 节点名称
     * @return Core\Logger\LoggerInterface
     */
    public static function logger($name = 'default')
    {
        return self::get('logger', $name);
    }

    /**
     * 返回视图对象
     *
     * @return Core\View\ViewInterface
     */
    public static function view()
    {
        return self::get('view');
    }

	/**
	 * 配置信息处理对象
	 *
	 * @return Core\Config
	 */
	public static function config()
	{
		return self::get('config');
	}

    /**
     * 注入对象
     *
     * @param string $name 名称
     * @param mixed $definition 定义
     * @param bool $singleton 是否单一实例
     * @return $this
     */
    public static function set($name, $definition, $singleton = true)
    {
        return self::$container->set($name, $definition, $singleton);
    }

    /**
     * 从容器获取
     *
     * @param string $name
     * @return mixed
     */
    public static function get($name)
    {
        return call_user_func_array(array(self::$container, 'get'), func_get_args());
    }

    /**
     * 获取容器对象
     *
     * @return \Core\Container
     */
    public static function container()
    {
        return self::$container;
    }

    /**
     * 静态魔术方法
     *
     * @param string $method
     * @param array $params
     * @return mixed
     * @throws InvalidArgumentException
     */
    public static function __callStatic($method, $params)
    {
        if (substr($method, 0, 3) == 'get') {
            $name = strtolower(substr($method, 3));
            if (self::$container->has($name)) {
                array_unshift($params, $name);
                return call_user_func_array([self::$container, 'get'], $params);
            }
        } elseif ($method === 'conf') { // 兼容旧版的获取配置方法
	        return call_user_func_array([self::config(), 'get'], $params);
        }
        throw new InvalidArgumentException("方法不存在: " . __CLASS__ . "::{$method}");
    }
}
