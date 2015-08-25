<?php

namespace Core\Exception;

/**
 * 页面不存在异常（404）
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Exception
 */
class HttpNotFoundException extends HttpException
{
    public function __construct($message = 'Page Not Found', $code = 404, \Exception $previous = NULL)
    {
        parent::__construct($code, $message, $previous);
    }
}
