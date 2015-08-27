<?php
namespace App\Controller;

use Core\Controller as Controller;
use Core\Lib\Cipher;

class MainController extends Controller
{

    public function init()
    {
        $this->setLayout('layout/layout');
        $this->setLayoutSection('header', 'layout/section/header');
    }

    public function indexAction()
    {

        $this->assign(array(
            'title' => '演示页面',
            'name' => 'world',
        ));

        return $this->display();
    }

}