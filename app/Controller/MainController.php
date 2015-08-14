<?php
namespace App\Controller;

use Core\ApiController as Controller;
use Core\Lib\Cipher;

class MainController extends Controller
{

    public function init()
    {

    }

    public function indexAction()
    {
        $this->assign(['foo'=>'bar']);
        $this->message('sadasddas');

        return $this->display();
    }

}