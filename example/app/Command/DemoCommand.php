<?php
namespace App\Command;

use Core\Command;
use App;

class DemoCommand extends Command
{
    public function logAction()
    {
        App::logger()->debug('debug log...');
        App::logger()->info("info log...");
        App::logger()->warn("warn log...");
        App::logger()->error("error log...");
        App::logger()->fatal("fatal log...");
    }
}