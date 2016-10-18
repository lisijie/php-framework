<?php

namespace Core\Exception;

/**
 * 页面不存在异常（404）
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Exception
 */
class HttpException extends \Exception
{
    public function __construct($code, $message = '')
    {
        parent::__construct($message, $code);
    }
}
