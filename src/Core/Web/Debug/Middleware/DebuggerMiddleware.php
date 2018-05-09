<?php
namespace Core\Web\Debug\Middleware;

use App;
use Core\Db;
use Core\Event\DbEvent;
use Core\Events;
use Core\Http\Cookie;
use Core\Http\Request;
use Core\Http\Response;
use Core\Middleware\MiddlewareInterface;
use Core\Web\Debug\Lib\Config;
use Core\Web\Debug\Lib\Storage;

class DebuggerMiddleware implements MiddlewareInterface
{
    private $sqlLogs = [];

    private $profileExtension = '';

    public function __construct()
    {
        if (!App::isCli()) {
            if (extension_loaded('xhprof')) {
                $this->profileExtension = 'xhprof';
                xhprof_enable(XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY | XHPROF_FLAGS_NO_BUILTINS);
            } elseif (extension_loaded('tideways_xhprof')) {
                $this->profileExtension = 'tideways_xhprof';
                tideways_xhprof_enable(TIDEWAYS_XHPROF_FLAGS_MEMORY | TIDEWAYS_XHPROF_FLAGS_MEMORY_MU | TIDEWAYS_XHPROF_FLAGS_MEMORY_PMU | TIDEWAYS_XHPROF_FLAGS_CPU);
            }
            Events::on(Db::class, Db::EVENT_QUERY, function (DbEvent $event) {
                $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
                $service = $model = $controller = '';
                foreach ($backtrace as $item) {
                    if (isset($item['file'])) {
                        if (strpos($item['file'], 'lisijie/framework') !== false) {
                            continue;
                        }
                        if (!$service && substr($item['file'], -11) == 'Service.php') {
                            $service = basename($item['file']) . ":{$item['line']}";
                        } elseif (!$model && substr($item['file'], -9) == 'Model.php') {
                            $model = basename($item['file']) . ":{$item['line']}";
                        } elseif (!$controller && substr($item['file'], -14) == 'Controller.php') {
                            $controller = basename($item['file']) . ":{$item['line']}";
                        }
                    }
                }
                $this->sqlLogs[] = [
                    'service' => $service,
                    'model' => $model,
                    'controller' => $controller,
                    'time' => $event->getTime() * 1000000, // 微秒
                    'sql' => $event->getSql(),
                    'params' => $event->getParams(),
                ];
            });
        }
    }

    /**
     * 处理请求
     *
     * @param Request $request
     * @param callable $next
     * @return Response
     */
    public function process(Request $request, callable $next)
    {

        if (App::isCli()
            || substr(CUR_ROUTE, 0, 6) == 'debug/'
            || (!$request->getCookieParam(Config::$startCookieName) && !$request->getQueryParam(Config::$debugParamName))
        ) {
            return $next();
        }

        $response = $next();

        $meta = [
            'route' => CUR_ROUTE,
            'url' => (string)$request->getUri()->getPath(),
            'requestTime' => $request->getServerParam('REQUEST_TIME'),
            'method' => $request->getMethod(),
            'responseHeaders' => $response->getHeaders(),
            'get' => $_GET,
            'post' => $_POST,
            'files' => $_FILES,
            'cookies' => $_COOKIE,
            'server' => $_SERVER,
            'startTime' => START_TIME,
            'execTime' => round((microtime(true) - START_TIME) * 1000000), // 微秒
            'memoryUsage' => memory_get_peak_usage(true),
            'sqlLogs' => $this->sqlLogs,
        ];

        $profile = [];
        if ($this->profileExtension == 'xhprof') {
            $profile = xhprof_disable();
        } elseif ($this->profileExtension == 'tideways_xhprof') {
            $profile = tideways_xhprof_disable();
        }

        $data = [
            'meta' => $meta,
            'sql' => $this->sqlLogs,
            'profile' => $profile,
        ];

        $fileKey = (new Storage())->save($data);

        $response = $response->withCookie(new Cookie(Config::$traceCookieName, $fileKey));
        return $response;
    }
}