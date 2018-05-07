<?php
namespace Core\Router;

use Core\Exception\HttpException;

/**
 * 请求方法不允许的异常
 * @package Core\Router
 */
class MethodNotAllowedException extends HttpException
{
    public function __construct()
    {
        parent::__construct(405, '405 Method Not Allowed');
    }
}