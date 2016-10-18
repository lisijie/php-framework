<?php

namespace Core\Exception;

/**
 * APP异常类
 *
 * 用于用户操作出错之类的一般性错误，可在页面展示出来的错误信息，不可用于提示文件路径或数据库帐号错误之类的敏感信息。
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Exception
 */
class AppException extends \Exception
{

}
