<?php
namespace App\Controller;

use Core\Controller;
use App;

class HomeController extends Controller
{
    public function indexAction()
    {
        return $this->serveJSON(['name' => 'lixiaoxie', 'age' => 10]);
    }

    public function dbAction()
    {
        App::db()->select("select * from test");
    }
}