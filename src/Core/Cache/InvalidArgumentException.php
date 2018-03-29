<?php
namespace Core\Cache;

use Psr\SimpleCache\InvalidArgumentException as InvalidArgumentExceptionInterface;

class InvalidArgumentException extends CacheException implements InvalidArgumentExceptionInterface
{

}