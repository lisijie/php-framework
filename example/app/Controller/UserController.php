<?php
namespace App\Controller;

use Core\Controller;

class UserController extends Controller
{
    public function listAction()
    {
        return $this->response->setContent('user/list');
    }

    public function infoAction()
    {
        $id = $this->request->getParam('id');

        return $this->serveJSON(['id' => $id]);
    }
}