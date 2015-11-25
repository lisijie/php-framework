<?php
namespace Core\Bootstrap;

use App;
use Core\Router\Router;
use Core\Logger\Logger;
use Core\Db;
use Core\Session\Session;
use Core\Session\Handler\Memcached;
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
        if (App::conf('app', 'timezone')) {
            date_default_timezone_set(App::conf('app', 'timezone'));
        } elseif (ini_get('date.timezone') == '') {
            date_default_timezone_set('Asia/Shanghai'); //设置默认时区为中国时区
        }

        //注册异常处理器
        if (class_exists('\\App\\Exception\\Handler')) {
            \App\Exception\Handler::factory(App::logger(), DEBUG)->register();
        } else {
            \Core\Exception\Handler::factory(App::logger(), DEBUG)->register();
        }
	}

    //注册DB初始化方法
	public function initDb($name)
	{
        $config = App::conf('app', 'database');
        if (!isset($config[$name])) {
            throw new \InvalidArgumentException("数据配置不存在: {$name}");
        }
        $config = $config[$name];
        $db = new Db($config);
        if (isset($config['slow_log']) && $config['slow_log']) { // 慢查询日志
            $db->addHook(Db::TAG_AFTER_QUERY, function($data) use($config) {
                if ($data['time'] > $config['slow_log']) {
                    $logger = App::logger('database');
                    $logger->debug("\nROUTE: ".CUR_ROUTE."\nSQL: {$data['sql']}\nDATA: ".json_encode($data['data'])."\nTIME: {$data['time']}\nMETHOD: {$data['method']}\n");
                }
            });
        }
        return $db;
	}

	public function initSession()
	{
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
	}

	public function initCache($name)
	{
        $config = App::conf('app','cache', array());
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
        $options = App::conf('app', 'router', array());
        $router = Router::factory($options);
        $router->setConfig(App::conf('route'));
        return $router;
	}

    //注册日志记录器
	public function initLogger($name)
	{
        $config = App::conf('app', 'logger', array());
        $logger = new Logger($name);
        $logger->setTimeZone(new \DateTimeZone('PRC'));
        if (isset($config[$name])) {
            foreach ($config[$name] as $conf) {
                $class = '\\Core\\Logger\\Handler\\' . $conf['handler'];
                $logger->setHandler(new $class($conf['config']), $conf['level']);
            }
        }
        return $logger;
	}

    //初始化视图模板对象
    public function initView()
    {
        $viewConf = App::conf('app', 'view');
        return ViewFactory::create($viewConf['engine'], $viewConf['options']);
    }
}
