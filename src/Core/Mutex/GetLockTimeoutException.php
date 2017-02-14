<?php

namespace Core\Mutex;

use Core\Exception\CoreException;

/**
 * 获取锁超时的异常类型
 * @package Core\Mutex
 */
class GetLockTimeoutException extends CoreException
{
    public function __construct($name, $timeout)
    {
        $message = "failed to get lock '{$name}' in {$timeout} seconds.";
        parent::__construct($message, 0);
    }
}