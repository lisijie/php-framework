<?php
namespace Core\Bootstrap;

use App;
use Core\Cache\CacheInterface;
use Core\Db;
use Core\Event\DbEvent;
use Core\Lib\VarDumper;
use Core\Logger\Logger;
use Core\Router\Router;
use Core\Session\Handler\Memcached;
use Core\Session\Session;
use Core\View\ViewFactory;

/**
 * 默认引导程序
 *
 * 执行应用的初始化工作，注册核心对象的初始化方法，使用者可自由定制引导程序，注册新的全局对象或者改变框架的默认行为。
 *
 * @package Core\Bootstrap
 */
class Bootstrap implements BootstrapInterface
{

    public function startup()
    {
        //设置时区
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
    }

    //注册DB初始化方法
    public function initDb($name)
    {
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
        return $db;
    }

    public function initSession()
    {
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
        return $session;
    }

    public function initCache($name)
    {
        $config = App::config()->get('app', 'cache', []);
        if ($name == 'default' && isset($config['default'])) {
            $name = $config['default'];
        }
        if (!isset($config[$name])) {
            throw new \InvalidArgumentException("缓存配置不存在: {$name}");
        }
        $driver = isset($config[$name]['driver']) ? $config[$name]['driver'] : '';
        $config = isset($config[$name]['config']) ? $config[$name]['config'] : [];
        if (!class_exists($driver)) {
            throw new \RuntimeException("找不到缓存驱动类: {$driver}");
        }
        $obj = new $driver($config);
        if (!($obj instanceof CacheInterface)) {
            throw new \RuntimeException("类 {$driver} 没有实现 CacheInterface 接口.");
        }
        return $obj;
    }

    //注册路由
    public function initRouter()
    {
        if (PHP_SAPI == 'cli') {
            $options = [
                'default_route' => 'help/index', //默认路由
            ];
            $router = Router::factory('Console', $options);
        } else {
            $config = App::config()->get('app', 'router', []);
            $router = Router::factory($config['type'], $config['options']);
            $router->addConfig(App::config()->get('route'));
        }
        return $router;
    }

    //注册日志记录器
    public function initLogger($channel)
    {
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
        return $logger;
    }

    //初始化视图模板对象
    public function initView()
    {
        $viewConf = App::config()->get('app', 'view');
        return ViewFactory::create($viewConf['engine'], $viewConf['options']);
    }
}
