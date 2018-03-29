<?php
namespace Core\Cache;

use Core\Exception\CoreException;
use Psr\SimpleCache\CacheException as CacheExceptionInterface;

/**
 * 缓存异常类
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Cache
 */
class CacheException extends CoreException implements CacheExceptionInterface
{

}
