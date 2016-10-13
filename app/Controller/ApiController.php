<?php
/**
 * Created by PhpStorm.
 * User: jesse
 * Date: 16/9/29
 * Time: 下午8:58
 */

namespace App\Controller;

use Core\JsonController;

class ApiController extends JsonController
{
    public function infoAction()
    {
        $this->assign('info', ['id' => 1, 'name' => 'test']);
        return $this->display();
    }

    public function testAction()
    {
        $data = [
            'get' => $this->getQuery(),
            'post' => $this->getPost(),
            'headers' => $this->request->headers(),
            'raw' => file_get_contents("php://input"),
            'files' => $this->request->getFiles(),
            'cookies' => $this->request->cookies()
        ];
        $this->assign($data);
        return $this->display();
    }

}