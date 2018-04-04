<?php
namespace Core\Container;

use Core\Exception\CoreException;
use Psr\Container\NotFoundExceptionInterface;

class NotFoundException extends CoreException implements NotFoundExceptionInterface
{

}