<?php
namespace App\Controller;

use Core\Controller;

class UserController extends Controller
{
    public function listAction()
    {

    }

    public function infoAction()
    {
        $id = $this->request->getAttribute('id');

        return $this->serveJSON(['id' => $id]);
    }
}