<?php
namespace Core\Bootstrap;

use App;
use Core\Event\DbEvent;
use Core\Event\Event;
use Core\Router\Router;
use Core\Logger\Logger;
use Core\Db;
use Core\Session\Session;
use Core\Session\Handler\Memcached;
use Core\View\ViewFactory;
use Core\Lib\VarDumper;

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
        if (class_exists('\\App\\Exception\\Handler')) {
            \App\Exception\Handler::factory(App::logger())->register();
        } else {
            \Core\Exception\Handler::factory(App::logger())->register();
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
				App::logger('database')->debug("[".CUR_ROUTE."] [{$name}] [{$time}] [{$sql}] {$params}");
			} else {
				$logSlow = $event->getSender()->getOption('log_slow');
                if ($logSlow && $event->getTime() >= $logSlow) {
	                App::logger('database')->warn("[".CUR_ROUTE."] [$name] [{$time}] [{$sql}] {$params}");
                }
            }
		});
        return $db;
	}

	public function initSession()
	{
		$config = App::config()->get('app', 'session', array());
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
		$session->start();
		return $session;
	}

	public function initCache($name)
	{
        $config = App::config()->get('app','cache', array());
        if ($name == 'default' && isset($config['default'])) {
            $name = $config['default'];
        }
        if (!isset($config[$name])) {
            throw new \InvalidArgumentException("缓存配置不存在: {$name}");
        }
        return \Core\Cache\Cache::factory($name, $config[$name]);
	}

    //注册路由
	public function initRouter()
	{
        $options = App::config()->get('app', 'router', array());
        $router = Router::factory($options);
        $router->setConfig(App::config()->get('route'));
        return $router;
	}

    //注册日志记录器
	public function initLogger($name)
	{
        $config = App::config()->get('app', 'logger', array());
		$timezone = App::config()->get('app', 'timezone', 'UTC');
        $logger = new Logger($name);
        $logger->setTimeZone(new \DateTimeZone($timezone));
		if ($name != 'default' && !isset($config[$name])) {
			$name = 'default';
		}
        if (isset($config[$name])) {
            foreach ($config[$name] as $conf) {
                $handlerClass = '\\Core\\Logger\\Handler\\' . $conf['handler'];
	            $handler = new $handlerClass($conf['config']);
	            if (!empty($conf['formatter'])) {
		            $formatterClass = '\\Core\\Logger\\Formatter\\' . $conf['formatter'];
		            $formatter = new $formatterClass();
		            $handler->setFormatter($formatter);
	            }
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
