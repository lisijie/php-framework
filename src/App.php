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
foreach (['APP_PATH', 'DATA_PATH'] as $name) {
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

//注册自动加载
ClassLoader::getInstance()
    ->registerNamespace('Core', __DIR__ . '/Core')
    ->registerNamespace('App', rtrim(APP_PATH, DIRECTORY_SEPARATOR))
    ->register();

use Core\Bootstrap\BootstrapInterface;
use Core\Config;
use Core\Container\Container;
use Core\Container\ServiceProvider;
use Core\Db;
use Core\Environment;
use Core\Event\DbEvent;
use Core\Events;
use Core\Exception\CoreException;
use Core\Exception\HttpException;
use Core\Exception\HttpNotFoundException;
use Core\Http\Request;
use Core\Http\Response;
use Core\Lib\VarDumper;
use Core\Logger\Logger;
use Core\Middleware\MiddlewareInterface;
use Core\Router\ConsoleRouter;
use Core\Router\HttpRouter;
use Core\Session\Handler\Memcached;
use Core\Session\Session;

class App extends Events
{
    /**
     * 是否调试模式
     * @var bool
     */
    private static $debug = false;

    /**
     * SQL调试
     * @var bool
     */
    private static $sqlDebug = false;

    /**
     * @var Container
     */
    private static $container;

    /**
     * @var Core\Middleware\MiddlewareInterface[]
     */
    private static $middlewares = [];

    /**
     * 执行引导程序
     *
     * 先调用所有init开头的方法，最后调用startup方法初始化
     *
     * @param BootstrapInterface $bootstrap
     */
    public static function bootstrap(BootstrapInterface $bootstrap = null)
    {
        $config = new Config(CONFIG_PATH, Environment::getEnvironment());
        self::$container = new Container();
        self::$container->addServiceProvider(new ServiceProvider(self::$container, $config->get('components')));
        self::set('config', $config);
        // 设置时区
        if (App::config()->get('app', 'timezone')) {
            date_default_timezone_set(App::config()->get('app', 'timezone'));
        } elseif (ini_get('date.timezone') == '') {
            date_default_timezone_set('Asia/Shanghai'); //设置默认时区为中国时区
        }
        //注册异常处理器
        if (class_exists('\\App\\Exception\\ErrorHandler')) {
            (new \App\Exception\ErrorHandler(App::logger()))->register();
        } else {
            (new \Core\Exception\ErrorHandler(App::logger()))->register();
        }
        if ($bootstrap) {
            $bootstrap->startup();
        }
    }

    /**
     * 运行应用并输出结果
     *
     * 流程：
     * 1. 实例化 Request 对象
     * 2. 路由解析，解析出路由地址和路由参数
     * 3. 根据路由地址解析出控制器类名和方法名
     * 4. 执行控制器方法，返回 Response 对象
     * 5. 执行 Response::send() 方法输出结果
     */
    public static function run()
    {
        if (self::isCli()) {
            $router = new ConsoleRouter();
            $router->setDefaultRoute('help/index');
            $router->registerNamespace('App\\Command', 'Command');
            $router->registerNamespace('Core\\Console', 'Command');
            self::set('router', $router, true);
            //当前路由地址
            define('CUR_ROUTE', $router->getRoute());
            list($class, $action) = $router->resolve();
            if (empty($class) || !class_exists($class) || !is_subclass_of($class, \Core\Command::class, true)) {
                die('command not found!');
            }
            $controller = new $class();
            $controller->execute($action, $router->getParams());
        } else {
            $config = App::config()->get('app', 'router', []);
            $router = new HttpRouter($config);
            $router->registerNamespace('App\\Controller', 'Controller');
            $router->addConfig(App::config()->get('route'));
            $request = new Request();
            $response = new Response();
            self::set('router', $router, true);
            self::set('request', $request, true);
            self::set('response', $response, true);
            $response = self::process($request, $response);
            $response->send();
        }
    }

    /**
     * 注册中间件
     *
     * @param MiddlewareInterface $middleware
     */
    public static function add(MiddlewareInterface $middleware)
    {
        self::$middlewares[] = $middleware;
    }

    /**
     * 处理请求
     *
     * @param Request $request
     * @param Response $response
     * @return Response 返回response对象
     * @throws HttpException
     * @throws HttpNotFoundException
     */
    public static function process(Request $request, Response $response)
    {
        $router = self::router();
        // 将路由地址解析为对应的控制器名和方法名
        list($controllerName, $actionName) = $router->resolve($request);
        // 控制器不存在抛出404异常
        if (empty($controllerName) || !class_exists($controllerName) || !is_subclass_of($controllerName, \Core\Controller::class, true)) {
            throw new HttpNotFoundException();
        }
        //当前路由地址
        define('CUR_ROUTE', $router->getRoute());
        try {
            $handler = function () use ($request, $response, $controllerName, $actionName, $router) {
                $controller = new $controllerName($request, $response);
                $controller->init();
                self::set('controller', $controller);
                $response = $controller->execute($actionName, $router->getParams());
                return $response;
            };
            // 中间件调用链
            if (!empty(self::$middlewares)) {
                for ($i = count(self::$middlewares) - 1; $i >= 0; $i--) {
                    $middleware = self::$middlewares[$i];
                    $handler = function () use ($request, $response, $handler, $middleware) {
                        return $middleware->process($request, $response, $handler);
                    };
                }

            }
            return $handler();
        } catch (BadMethodCallException $e) {
            self::logger()->debug($e);
            throw new HttpNotFoundException();
        }
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
     * 设置调试模式
     *
     * @param $bool
     */
    public static function setDebug($bool)
    {
        self::$debug = (bool)$bool;
    }

    /**
     * 返回是否调试模式
     *
     * @return bool
     */
    public static function isDebug()
    {
        return self::$debug;
    }

    /**
     * 设置调试模式
     *
     * @param $bool
     */
    public static function setSqlDebug($bool)
    {
        self::$sqlDebug = (bool)$bool;
    }

    /**
     * 返回是否调试模式
     *
     * @return bool
     */
    public static function isSqlDebug()
    {
        return self::$sqlDebug;
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
                if (is_file(LANG_PATH . DS . $filename)) {
                    $lang = [];
                    include LANG_PATH . DS . $filename;
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
                if (!is_file(LANG_PATH . DS . $filename)) {
                    throw new InvalidArgumentException("lang file {$filename} not exists.");
                }
                include LANG_PATH . DS . $filename;
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
     * @param string $node 节点名称
     * @return Core\Db
     */
    public static function db($node = 'default')
    {
        $name = "db:{$node}";
        if (!self::has($name)) {
            $config = App::config()->get('app', 'database');
            if (!isset($config[$name])) {
                throw new \InvalidArgumentException("数据配置不存在: {$name}");
            }
            $config = $config[$name];
            $db = new Db($config);
            $db->on(Db::EVENT_QUERY, function (DbEvent $event) use ($name) {
                $time = number_format($event->getTime(), 3) . 's';
                $params = VarDumper::export($event->getParams());
                $sql = $event->getSql();
                if (App::isSqlDebug()) {
                    App::logger('database')->debug("[" . CUR_ROUTE . "] [{$name}] [{$time}] [{$sql}] {$params}");
                } else {
                    $logSlow = $event->getSender()->getOption('log_slow');
                    if ($logSlow && $event->getTime() >= $logSlow) {
                        App::logger('database')->warn("[" . CUR_ROUTE . "] [$name] [{$time}] [{$sql}] {$params}");
                    }
                }
            });
            self::set($name, $db);
        }
        return self::get($name);
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
     * @return Core\Router\RouterInterface
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
        if (!self::has('session')) {
            $config = App::config()->get('app', 'session', []);
            $session = new Session();
            if (isset($config['type'])) {
                switch ($config['type']) {
                    case 'file':
                        if (!empty($config['file']['save_path'])) {
                            $session->setSavePath($config['file']['save_path']);
                        }
                        break;
                    case 'memcached':
                        $session->setHandler(new Memcached($config['memcached']['servers']));
                        break;
                }
            }
            // $session->start();
            self::set('session', $session, true);
        }
        return self::get('session');
    }

    /**
     * 返回缓存对象
     *
     * @return Core\Cache\CacheInterface
     */
    public static function cache()
    {
        return self::get('cache');
    }

    /**
     * 返回日志对象
     *
     * @param string $channel 通道名称
     * @return Core\Logger\LoggerInterface
     */
    public static function logger($channel = '')
    {
        if (empty($channel)) {
            $channel = PHP_SAPI == 'cli' ? 'console' : 'default';
        }
        $name = "logger:{$channel}";
        if (!self::has($name)) {
            $config = App::config()->get('app', 'logger', []);
            $timezone = App::config()->get('app', 'timezone', 'UTC');
            $logger = new Logger($channel);
            $logger->setTimeZone(new \DateTimeZone($timezone));
            if ($channel != 'default' && !isset($config[$channel])) {
                $channel = 'default';
            }
            if (isset($config[$channel])) {
                foreach ($config[$channel] as $conf) {
                    $handlerClass = $conf['handler'];
                    if (!class_exists($handlerClass)) {
                        throw new \RuntimeException('找不到日志处理类: ' . $handlerClass);
                    }
                    $handler = new $handlerClass($conf['config']);
                    // 日志格式化器配置
                    if (!empty($conf['formatter'])) {
                        $formatterClass = $conf['formatter'];
                        if (!class_exists($formatterClass)) {
                            throw new \RuntimeException('找不到日志格式化类: ' . $formatterClass);
                        }
                        $formatter = new $formatterClass();
                        $handler->setFormatter($formatter);
                    }
                    // 设置日志时间格式
                    if (!empty($conf['date_format'])) {
                        $handler->getFormatter()->setDateFormat($conf['date_format']);
                    }
                    $logger->setHandler($handler, $conf['level']);
                }
            }
            self::set($name, $logger, true);
        }
        return self::get($name);
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
     * 添加对象
     *
     * $definition 可以是一个配置信息，格式为：
     *   ['class' => className, 'param1' => 'value1', 'param2' => 'value2' ...]
     * 示例化时，如果构造函数有定义了对应名称的参数，则传给构造函数，否则通过 setter 函数赋值给对象。
     *
     * @param string $name 名称
     * @param mixed $definition 定义
     * @param bool $shared 是否共享实例
     * @return bool
     */
    public static function set($name, $definition, $shared = true)
    {
        return self::$container->set($name, $definition, $shared);
    }

    /**
     * 从容器获取
     *
     * @param string $name
     * @return mixed
     * @throws CoreException
     */
    public static function get($name)
    {
        return self::$container->get($name);
    }

    /**
     * 检查容器中是否存在某个名称
     *
     * @param string $name
     * @return bool
     */
    public static function has($name)
    {
        return self::$container->has($name);
    }

    /**
     * 返回容器对象
     *
     * @return Container
     */
    public static function container()
    {
        return self::$container;
    }
}
