<?php
namespace Core\Container;

use Core\Exception\CoreException;
use Psr\Container\NotFoundExceptionInterface;

class ContainerException extends CoreException implements NotFoundExceptionInterface
{

}