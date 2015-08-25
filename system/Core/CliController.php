<?php

namespace Core;

use Core\Http\Response;
use Core\Exception\AppException;

class CliController extends Controller
{

    /**
     * 执行当前控制器方法
     *
     * @param string $actionName 方法名
     * @param array $params 参数列表
     * @return Response|mixed
     * @throws AppException
     */
    public function runActionWithParams($actionName, $params = array())
    {
        if (!method_exists($this, $actionName)) {
            throw new \BadMethodCallException("方法不存在: {$actionName}");
        }

        $method = new \ReflectionMethod($this, $actionName);
        if (!$method->isPublic()) {
            throw new \BadMethodCallException("调用非公有方法: {$actionName}");
        }
        $args = array();
        $methodParams = $method->getParameters();
        if (!empty($methodParams)) {
            foreach ($methodParams as $key => $p) {
                $default = $p->isOptional() ? $p->getDefaultValue() : null;
                $value = isset($params[$key]) ? $params[$key] : $default;
                if (null === $value) {
                    throw new AppException(get_class($this)."::{$actionName}() 缺少参数: " . $p->getName());
                }
                $args[] = $value;
            }
        }
        $result = $method->invokeArgs($this, $args);
        if ($result instanceof Response) {
            return $result;
        } elseif (null !== $result) {
            $this->response->setContent(strval($result));
        }
        return $this->response;
    }
}
