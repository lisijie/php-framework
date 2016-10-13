<?php
namespace Core\Lib;

use Core\Db;

/**
 * 性能分析器
 *
 * @package Core\Lib
 */
class Profiler
{
    private $xhporfEnabled = false;
    private $xhprofUrl = '/xhprof/html/'; // XHPROF结果显示地址
    private $showStatusBar = false;
    private $dataPath;

    public function __construct()
    {
        $this->xhporfEnabled = extension_loaded('xhprof');
        if (isset($_GET['debug']) && $_GET['debug']) {
            $this->showStatusBar = true;
        }
        $this->dataPath = DATA_PATH . 'xhprof/';
    }

    public function setXhprofUrl($url)
    {
        $this->xhprofUrl = $url;
    }

    public function setDataPath($path)
    {
        $this->dataPath = $path;
    }

    public function start()
    {
        if (PHP_SAPI == 'cli') {
            return false;
        }
        if ($this->xhporfEnabled) {
            require __DIR__ . '/Xhprof/xhprof_lib.php';
            require __DIR__ . '/Xhprof/xhprof_runs.php';
            xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
        }

        $this->register();
    }

    private function register()
    {
        register_shutdown_function(function () {
            $execTime = microtime(true) - START_TIME;
            $memoryUsage = round((memory_get_usage() - START_MEMORY_USAGE) / 1024, 2);
            $info = '执行时间: ' . round($execTime, 2) . 's, 内存使用: ' . $memoryUsage . 'KB';
            $info .= ", 查询次数: " . Db::$queryCount . ", 慢查询数量: " . Db::$slowCount;
            if ($this->xhporfEnabled) {
                $route = str_replace('/', '_', CUR_ROUTE);
                $xhprofData = xhprof_disable();
                $dir = DATA_PATH . 'xhprof/';
                if (is_dir($dir) || @mkdir($dir)) {
                    $xhprofRuns = new \XHProfRuns_Default($dir);
                    $runId = $xhprofRuns->save_run($xhprofData, $route);
                    $info .= ', 性能分析: <a href="' . $this->xhprofUrl . '?run=' . $runId . '&source=' . $route . '" target="_blank">' . $runId . '</a>';
                }
            }
            $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            $isGet = !empty($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) === 'get';
            if ($this->showStatusBar && $isGet && !$isAjax) {
                echo '<div style="padding:5px 0;background:#faebcc;text-align:center;">';
                echo $info;
                echo '</div>';
            }
        });
    }
}