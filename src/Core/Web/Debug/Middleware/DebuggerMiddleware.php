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

class DebuggerMiddleware implements MiddlewareInterface
{

    private $logPath;

    private $xhprofLogPath;

    private $cookieName = 'debug_trace_id';

    private $sqlLogs = [];

    private $xhprofEnabled = false;

    public function __construct()
    {
        if (!App::isCli()) {
            $this->logPath = DATA_PATH . '/debug';
            $this->xhprofLogPath = $this->logPath . '/xhprof';
            if (!is_dir($this->xhprofLogPath)) {
                mkdir($this->xhprofLogPath, 0755, true);
            }
            $this->xhprofEnabled = extension_loaded('xhprof');
            if ($this->xhprofEnabled) {
                require dirname(__DIR__) . '/Lib/Xhprof/xhprof_lib.php';
                require dirname(__DIR__) . '/Lib/Xhprof/xhprof_runs.php';
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
                    'time' => $event->getTime(),
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
        if (App::isCli() || !$request->getQueryParam('_debug')) {
            return $next();
        }

        if ($this->xhprofEnabled) {
            xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
        }

        $response = $next();

        $data = [
            'route' => CUR_ROUTE,
            'request' => $request,
            'responseHeaders' => $response->getHeaders(),
            'contentLength' => $response->getContentLength(),
            'get' => $_GET,
            'post' => $_POST,
            'files' => $_FILES,
            'cookies' => $_COOKIE,
            'server' => $_SERVER,
            'startTime' => START_TIME,
            'execTime' => microtime(true) - START_TIME,
            'memoryUsage' => memory_get_usage(),
            'sqlLogs' => $this->sqlLogs,
        ];
        if ($this->xhprofEnabled) {
            $route = str_replace('/', '_', CUR_ROUTE);
            $xhprofData = xhprof_disable();
            $xhprofRuns = new \XHProfRuns_Default($this->xhprofLogPath);
            $runId = $xhprofRuns->save_run($xhprofData, $route);
            $data['xhprofRunId'] = $runId;
        }

        $filename = uniqid();
        file_put_contents("{$this->logPath}/{$filename}.log", serialize($data));

        $response = $response->withCookie(new Cookie($this->cookieName, $filename));
        return $response;
    }
}