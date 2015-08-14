<?php
namespace App\Command;

class DemoController extends Controller
{

	/**
	 * 测试
	 * 
	 * @param  [type] $foo [description]
	 * @return [type]      [description]
	 */
    public function indexAction($foo)
    {
        return $foo;
    }

}
