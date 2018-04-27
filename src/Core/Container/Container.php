<?php
namespace Core\Container;

use Core\Exception\CoreException;
use Psr\Container\ContainerInterface;
use ReflectionClass;

/**
 * 对象容器
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core
 */
class Container implements ContainerInterface
{
    /**
     * 对象定义
     * @var array
     */
    private $definitions = [];

    /**
     * 共享对象
     * @var array
     */
    private $sharedInstances = [];

    /**
     * @var []ServiceProviderInterface
     */
    private $serviceProviders = [];

    /**
     * 设置服务提供者
     *
     * @param ServiceProviderInterface $provider
     */
    public function addServiceProvider(ServiceProviderInterface $provider)
    {
        $this->serviceProviders[] = $provider;
    }

    /**
     * 添加对象
     *
     * $definition 可以是一个配置信息，格式为：
     *   ['class' => className, 'param1' => 'value1', 'param2' => 'value2' ...]
     * 示例化时，如果构造函数有定义了对应名称的参数，则传给构造函数，否则通过 setter 函数赋值给对象。
     *
     * @param string $name 名称
     * @param mixed $definition 定义
     * @param bool $shared 是否共享实例
     * @return bool
     */
    public function set($name, $definition, $shared)
    {
        $this->definitions[$name] = $definition;
        if ($shared) {
            $this->sharedInstances[$name] = null;
        } else {
            unset($this->sharedInstances[$name]);
        }
        return true;
    }

    /**
     * 从容器获取
     *
     * @param string $name
     * @return mixed
     * @throws CoreException
     */
    public function get($name)
    {
        if (!isset($this->definitions[$name])) {
            foreach ($this->serviceProviders as $provider) {
                if ($provider->has($name)) {
                    return $provider->get($name);
                }
            }
            throw new NotFoundException("对象名称未注册: {$name}");
        }

        if (array_key_exists($name, $this->sharedInstances) && (null !== $this->sharedInstances[$name])) {
            return $this->sharedInstances[$name];
        }
        $definition = $this->definitions[$name];
        $object = null;
        switch ($definition) {
            case is_callable($definition):  // 注册的是一个函数或闭包
                $object = $definition();
                break;
            case is_object($definition): // 已经实例化的对象
                $object = $definition;
                break;
            case is_string($definition) && class_exists($definition): // 注册的是类名
                $refClass = new ReflectionClass($definition);
                $object = $refClass->newInstance();
                break;
            case is_array($definition) && isset($definition['class']) && class_exists($definition['class']): // 指定配置格式
                $refClass = new ReflectionClass($definition['class']);
                unset($definition['class']);
                $args = [];
                if ($method = $refClass->getConstructor()) {
                    foreach ($method->getParameters() as $parameter) {
                        $paramName = $parameter->getName();
                        if (isset($definition[$paramName])) {
                            $args[] = $definition[$paramName];
                            unset($definition[$paramName]);
                        } elseif (!$parameter->isOptional()) {
                            throw new ContainerException("构建 {$refClass->getName()} 失败，构造函数缺少参数: {$parameter->getName()}");
                        }
                    }
                }
                $object = $refClass->newInstanceArgs($args);
                // 剩余的参数传给 setter 方法（如果有的话）
                foreach ([$definition, $args] as $params) {
                    foreach ($params as $key => $value) {
                        if (is_string($key) && method_exists($object, "set{$key}")) {
                            call_user_func_array([$object, "set{$key}"], [$value]);
                        }
                    }
                }
                break;
            default:
                $object = $definition;
                break;
        }
        if (array_key_exists($name, $this->sharedInstances)) {
            $this->sharedInstances[$name] = $object;
        }
        return $object;
    }

    /**
     * 检查容器中是否存在某个名称
     *
     * @param string $name
     * @return bool
     */
    public function has($name)
    {
        $ok = array_key_exists($name, $this->definitions);
        if (!$ok) {
            foreach ($this->serviceProviders as $provider) {
                if ($provider->has($name)) {
                    return true;
                }
            }
        }
        return $ok;
    }
}