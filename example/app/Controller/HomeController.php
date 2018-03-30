<?php
namespace App\Controller;

use Core\Cache\CacheInterface;
use Core\Controller;
use App;

class HomeController extends Controller
{
    /**
     * @var CacheInterface
     */
    private $cache;

    public function init()
    {
        $this->cache = \App::get('cache');
    }

    public function indexAction()
    {
        $this->cache->set('foo', 'bar');
        return $this->serveJSON(['name' => 'lixiaoxie', 'age' => 10]);
    }

    public function dbAction()
    {
        App::db()->select("select * from test");
    }
}