<?php
namespace Core\Web\Debug\Controller;

use Core\Controller;
use Core\Http\Response;
use Core\View\Native;

class DebugController extends Controller
{
    public function indexAction()
    {
        $traceId = $this->getQuery('id', $this->getCookie('debug_trace_id'));
        $filename = DATA_PATH . '/debug/' . $traceId . '.log';

        if (!$traceId || !is_file($filename)) {
            exit('调试日志不存在。');
        }

        $data = file_get_contents($filename);
        $data = unserialize($data);

        return new Response(200, (new Native([]))->render(dirname(__DIR__) . '/View/debug.php', $data));
    }
}