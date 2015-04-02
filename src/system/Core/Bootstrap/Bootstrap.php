<?php
namespace Core\Bootstrap;

use App;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\Header;
use Core\Http\Cookies;
use Core\Router\Router;
use Core\Logger\Logger;
use Core\Db;
use Core\Session\Session;
use Core\Session\Handler\Memcached;


class Bootstrap implements BootstrapInterface
{

	public function startup()
	{
		//设置错误报告级别, 使用最严格的标准
		@error_reporting(E_ALL | E_STRICT);
		//关闭显示错误消息, 所有错误已经转换成异常, 并注册了默认异常处理器
		@ini_set('display_errors', DEBUG);
        //不使用魔术引用, php 5.4之后已废弃魔术引用
        if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
            die('当前应用不允许运行在 magic_quotes_gpc = on 的环境下，请到 php.ini 关闭。');
        }
        //设置时区
        if (App::conf('app', 'timezone')) {
            date_default_timezone_set(App::conf('app', 'timezone'));
        } elseif (ini_get('date.timezone') == '') {
            date_default_timezone_set('Asia/Shanghai'); //设置默认时区为中国时区
        }
		//注册错误处理函数
		set_error_handler(function ($code, $str, $file, $line) {
			throw new \ErrorException($str, $code, 0, $file, $line);
		});
		//注册shutdown函数
		register_shutdown_function(function() {
			$error = error_get_last();
			if ($error) {
				$errTypes = array(E_ERROR => 'E_ERROR', E_PARSE => 'E_PARSE', E_USER_ERROR => 'E_USER_ERROR');
				if (isset($errTypes[$error['type']])) {
					$info = $errTypes[$error['type']].": {$error['message']} in {$error['file']} on line {$error['line']}";
					App::getLogger()->error($info);
				}
			}
		});
        //注册异常处理器
        if (class_exists('\\App\\Exception\\Handler')) {
            \App\Exception\Handler::factory(App::getLogger(), DEBUG)->register();
        } else {
            \Core\Exception\Handler::factory(App::getLogger(), DEBUG)->register();
        }

	}

    //注册DB初始化方法
	public function initDb()
	{
        App::set('db', function($name = 'default') {
            static $instance = array();
            if (!isset($instance[$name])) {
                $config = App::conf('app', 'database');
                if (!isset($config[$name])) {
                    throw new \InvalidArgumentException("数据配置不存在: {$name}");
                }
                $db = new Db($config[$name]);
                $db->setLogger(App::get('logger'));
                $instance[$name] = $db;
            }
            return $instance[$name];
        }, false);
	}

	public function initSession()
	{
		App::set('session', function() {
			$config = App::conf('app', 'session', array());
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
		}, true);
	}

	public function initCache()
	{
        App::set('cache', function($name = 'default') {
            static $instances = array();
            if (!isset($instances[$name])) {
                $config = App::conf('app','cache');
                if ($name == 'default') {
                    $name = $config['default'];
                }
                $instances[$name] = \Core\Cache\Cache::factory($name, $config[$name]);
            }
            return $instances[$name];
        }, false);
	}

    //注册路由
	public function initRouter()
	{
        App::set('router', function () {
            $options = App::conf('app', 'router');
            $router = Router::factory($options);
            $router->setConfig(App::conf('route'));
            $router->setRequest(App::getRequest());
            return $router;
        }, true);
	}

    //注册输出对象
	public function initResponse()
	{
        App::set('response', new Response(), true);
	}

    //注册请求对象
	public function initRequest()
	{
		App::set('request', new Request(Header::createFrom($_SERVER), new Cookies($_COOKIE)), true);
	}

    //注册日志记录器
	public function initLogger()
	{
        App::set('logger', function ($name = 'default') {
            static $instances = array();
            if (!isset($instances[$name])) {
                $config = App::conf('app', 'logger', array());
                $logger = new Logger($name);
                $logger->setTimeZone(new \DateTimeZone('PRC'));
                if (isset($config[$name])) {
                    foreach ($config[$name] as $conf) {
                        $class = '\\Core\\Logger\\Handler\\' . $conf['handler'];
                        $logger->setHandler(new $class($conf['config']), $conf['level']);
                    }
                }
                $instances[$name] = $logger;
            }
            return $instances[$name];
        }, false);
	}
}
