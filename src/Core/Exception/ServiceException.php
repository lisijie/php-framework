<?php

namespace Core\Exception;

use Core\Exception\AppException;

/**
 * Service异常
 *
 * 当Service模块接口发生错误时，应当抛出Service异常，可以往下再派生具体类型的异常。
 *
 * @author lisijie <lsj86@qq.com>
 * @package App\Exception
 */
class ServiceException extends AppException
{

}