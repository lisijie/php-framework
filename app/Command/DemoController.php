<?php
namespace App\Command;

use Core\CliController as Controller;

class DemoController extends Controller
{
    public function testAction($name)
    {
        $this->stdout("hello, {$name}\n");
    }
}
