<?php
namespace Core\Middleware;

use Core\Http\Request;
use Core\Http\Response;

/**
 * 中间件接口
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Middleware
 */
interface MiddlewareInterface
{
    /**
     * 处理请求
     *
     * @param Request $request
     * @param callable $next
     * @return Response
     */
    public function process(Request $request, callable $next);
}