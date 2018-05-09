<?php
namespace Core\Web\Debug\Controller;

use Core\Controller;
use Core\Http\Cookie;
use Core\Http\Response;
use Core\Lib\Pager;
use Core\View\Native;
use Core\Web\Debug\Lib\Config;
use Core\Web\Debug\Lib\Profile;
use Core\Web\Debug\Lib\Storage;

class DebugController extends Controller
{
    /**
     * @var Native
     */
    private $view;

    /**
     * @var Storage
     */
    private $storage;

    public function init()
    {
        $this->view = new Native([
            'template_dir' => dirname(__DIR__) . '/View',
            'ext' => '.php',
        ]);
        $this->view->registerFunc('formatTime', function ($v) {
            return round($v / 1000, 1) . ' <span class="text-muted">ms</span>';
        });
        $this->view->registerFunc('formatSize', function ($v) {
            return round($v / 1024, 2) . ' <span class="text-muted">KB</span>';
        });
        $this->view->setLayout('layout');

        $this->storage = new Storage();
    }

    public function indexAction()
    {
        $page = $this->get('page', 1);
        $size = 20;

        $list = $this->storage->getList(($page - 1) * $size, $size, $total);
        $pager = new Pager($page, $size, $total);
        $data = [
            'status' => $this->getCookie(Config::$startCookieName) ? 'on' : 'off',
            'list' => $list,
            'pager' => $pager->makeHtml(),
        ];
        return new Response(200, $this->view->render('index', $data));
    }

    public function viewAction()
    {
        $traceId = $this->getQuery('id', $this->getCookie(Config::$traceCookieName));

        $content = $this->storage->get($traceId);

        if (!$content) {
            return new Response(200, '调试日志不存在。');
        }

        $profile = new Profile($content);

        $data = [
            'status' => $this->getCookie(Config::$startCookieName) ? 'on' : 'off',
            'meta' => $profile->getMeta(),
            'sqlLogs' => $profile->getSqlLogs(),
            'profile' => $profile->sort('ewt', $profile->getProfile()),
        ];

        return new Response(200, $this->view->render('view', $data));
    }

    public function startAction()
    {
        $this->setCookie(new Cookie(Config::$startCookieName, 1));
        return $this->goBack();
    }

    public function stopAction()
    {
        $this->setCookie(new Cookie(Config::$startCookieName, 0, -1));
        return $this->goBack();
    }

    public function clearAction()
    {
        $this->storage->clear();
        return $this->redirect(URL('debug/index'));
    }
}