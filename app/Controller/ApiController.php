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
		$this->assign('info', ['id'=>1, 'name'=>'test']);
		$this->display();
	}
}