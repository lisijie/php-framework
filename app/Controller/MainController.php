<?php
namespace App\Controller;

use Core\Controller;
use Core\Lib\Cipher;

class MainController extends Controller
{

    public function init()
    {

    }

    public function indexAction()
    {
        $this->assign('');

        $this->display();
    }

}