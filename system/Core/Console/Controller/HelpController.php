<?php
namespace Core\Console\Controller;

use Core\CliController as Controller;

class HelpController extends Controller
{

    public function indexAction($command = null)
    {
        return "hi";
    }

}
