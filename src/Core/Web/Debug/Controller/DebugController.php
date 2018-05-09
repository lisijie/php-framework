<?php
namespace Core\Web\Debug\Controller;

use Core\Controller;
use Core\Http\Response;
use Core\View\Native;
use Core\Web\Debug\Lib\Profile;
use Core\Web\Debug\Lib\Storage;

class DebugController extends Controller
{
    public function indexAction()
    {
        $traceId = $this->getQuery('id', $this->getCookie('debug_trace_id'));

        $content = (new Storage())->get($traceId);

        if (!$content) {
            return new Response(200, '调试日志不存在。');
        }

        $profile = new Profile($content);

        $data = [
            'meta' => $profile->getMeta(),
            'sqlLogs' => $profile->getSqlLogs(),
            'profile' => $profile->sort('ewt', $profile->getProfile()),
        ];

        $view = new Native([]);
        $view->registerFunc('formatTime', function ($v) {
            return round($v / 1000, 1) . ' <span class="text-muted">ms</span>';
        });
        $view->registerFunc('formatSize', function ($v) {
            return round($v / 1024 / 1024, 2) . ' <span class="text-muted">KB</span>';
        });

        return new Response(200, $view->render(dirname(__DIR__) . '/View/debug.php', $data));
    }

    public function clearAction()
    {
        (new Storage())->clear();
        return new Response(200, '日志已清空。');
    }
}