<?php
namespace Core\Middleware;

use Core\Http\Request;
use Core\Http\Response;

interface MiddlewareInterface
{
    public function process(Request $request, Response $response, callable $next);
}