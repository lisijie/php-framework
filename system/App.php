<?php
/**
 * 框架引导程序
 *
 * @author lisijie <lsj86@qq.com>
 */

//检查PHP版本，必须5.3以上
if (version_compare(PHP_VERSION, '5.3.0', '<')) {
    die('require PHP > 5.3.0 !');
}
//不使用魔术引用, php 5.4之后已废弃魔术引用
if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
    die('当前应用不允许运行在 magic_quotes_gpc = on 的环境下，请到 php.ini 关闭。');
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
    //require VENDOR_PATH . 'autoload.php';
}

//关闭显示错误消息, 所有错误已经转换成异常, 并注册了默认异常处理器
ini_set('display_errors', DEBUG);

//注册自动加载
$loader = ClassLoader::getInstance();
$loader->registerNamespace('Core', __DIR__ . '/Core');
$loader->registerNamespace('App', rtrim(APP_PATH, DIRECTORY_SEPARATOR));
$loader->register();

class App
{

    /**
     * 容器
     *
     * @var \Core\Container;
     */
    protected static $container;

    /**
     * 开始路由分发
     */
    public static function run()
    {
        $router = static::router();
        $router->parse();
        $_GET = array_merge($_GET, $router->getParams());
        $routeName = $router->getRoute();
        //当前路由地址
        define('CUR_ROUTE', $routeName);

        if (!preg_match('#^[a-z][a-z0-9/]+$#i', $routeName)) {
            throw new \Core\Exception\HttpNotFoundException('invalid request.');
        }

        $pos = strrpos($routeName, '/');
        $controllerName = str_replace('/', ' ', substr($routeName, 0, $pos));
        $controllerName = str_replace(' ', '\\', ucwords($controllerName));
        $actionName = substr($routeName, $pos + 1) . 'Action';
        $className = "\\App\\Controller\\{$controllerName}Controller";

        if (!class_exists($className)) {
            throw new \Core\Exception\HttpNotFoundException("controller not found: {$className}");
        }

        $class = new ReflectionClass($className);
        $request = static::request();
        if ($class->hasMethod($actionName)) {
            $method = new ReflectionMethod($className, $actionName);
            if ($method->isPublic()) {
                $args = array();
                $params = $method->getParameters();
                if (!empty($params)) {
                    foreach ($params as $p) {
                        $default = $p->isOptional() ? $p->getDefaultValue() : null;
                        $value = $request->get($p->getName(), $default);
                        if (null === $value) {
                            throw new \RuntimeException('缺少参数:' . $p->getName());
                        }
                        $args[] = $value;
                    }
                }
                $controller = new $className();
                static::set('controller', $controller);
                $controller->init();
                $method->invokeArgs($controller, $args);
                static::terminate();
            }
        }
        throw new \Core\Exception\HttpNotFoundException();
    }

    /**
     * 终止
     */
    public static function terminate()
    {
        $controller = static::get('controller');
        $controller->after();
        static::response()->send();
        exit(0);
    }

    /**
     * 执行引导程序
     *
     * 先调用所有init开头的方法，最后调用startup方法初始化
     *
     * @param \Core\Bootstrap\BootstrapInterface $bootstrap
     */
    public static function bootstrap(\Core\Bootstrap\BootstrapInterface $bootstrap = null)
    {
        static::$container = new \Core\Container();
        if (!is_object($bootstrap)) {
            $bootstrap = new \Core\Bootstrap\Bootstrap();
        }

        $class = new ReflectionClass($bootstrap);
        $methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            $methodName = $method->getName();
            if (substr($methodName, 0, 4) == 'init') {
                static::$container->set(lcfirst(substr($methodName, 4)), array($bootstrap, $methodName), true);
            }
        }
        $bootstrap->startup();
    }

    /**
     * 获取配置信息
     *
     * 优先从 config/{RUN_MODE}/ 下查找，如果不存在再从 config/ 下查找。
     *
     * @param string $file 配置文件
     * @param string $key
     * @param mixed $default
     * @param boolean $reload
     * @return bool|mixed|string
     */
    public static function conf($file, $key = '', $default = '', $reload = false)
    {
        static $allConfig = array();
        if ($reload || !isset($allConfig[$file])) {
            if (!preg_match('/^[a-z0-9\_]+$/i', $file)) return false;
            $fileName = CONFIG_PATH . $file . '.php';
            $diffName = CONFIG_PATH . RUN_MODE . '/' . $file . '.php';
            if (!is_file($diffName) && !is_file($fileName)) {
                die("配置文件不存在: {$file}");
            }
            if (is_file($fileName)) {
                $allConfig[$file] = include $fileName;
            }
            if (is_file($diffName)) {
                $diff = include $diffName;
                if (is_array($diff)) {
                    $allConfig[$file] = array_merge($allConfig[$file], $diff);
                }
            }
        }
        if (empty($key)) {
            return $allConfig[$file];
        } else {
            return isset($allConfig[$file][$key]) ? $allConfig[$file][$key] : $default;
        }
    }

    /**
     * 语言包解析
     *
     * 如果$langId不包含点号，则从公共语言包 common.php 文件搜索对应索引，如果
     * 公共语言包文件不存在，则直接返回 $langId。
     *
     * @param string $langId 语言ID,格式：文件名.数组key
     * @param array $params
     * @throws InvalidArgumentException
     * @return string
     */
    public static function lang($langId, $params = array())
    {
        static $cache = array();
        if (false === strpos($langId, '.')) {
            if (!isset($cache['common'])) {
                $filename = App::conf('app', 'lang', 'zh_CN') . "/language.php";
                if (is_file($filename)) {
                    $lang = array();
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
                $lang = array();
                $filename = App::conf('app', 'lang', 'zh_CN') . "/{$file}.php";
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
     * @throws Core\Exception\HttpException
     */
    public static function abort($code, $message = '')
    {
        throw new \Core\Exception\HttpException($message, $code);
    }

    /**
     * 获取DB实例
     *
     * @param string $name
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
     * @return Core\Cache\CacheInterface
     */
    public static function cache($name = 'default')
    {
        return self::get('cache', $name);
    }

    /**
     * 返回日志对象
     *
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
     * 注入对象
     *
     * @param string $name 名称
     * @param mixed $definition 定义
     * @param bool $singleton 是否单一实例
     * @return $this
     */
    public static function set($name, $definition, $singleton = true)
    {
        return static::$container->set($name, $definition, $singleton);
    }

    /**
     * 从容器获取
     *
     * @param string $name
     * @return mixed
     */
    public static function get($name)
    {
        return call_user_func_array(array(static::$container, 'get'), func_get_args());
    }

    /**
     * 获取容器对象
     *
     * @return \Core\Container
     */
    public static function container()
    {
        return static::$container;
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
            if (static::$container->has($name)) {
                array_unshift($params, $name);
                return call_user_func_array(array(static::$container, 'get'), $params);
            }
        }
        throw new InvalidArgumentException("方法不存在: " . __CLASS__ . "::{$method}");
    }
}
