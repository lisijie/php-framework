<?php
namespace App\Command;

use Core\Command;

class DemoCommand extends Command
{
    public function testAction($name)
    {
        $this->stdout("hello, {$name}\n");
    }
}
