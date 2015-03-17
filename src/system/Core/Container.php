<?php
namespace Core;

/**
 * 对象容器
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core
 */
class Container
{
	protected $definitions = array();

	protected $singletons = array();

	public function set($name, $definition, $singleton = false)
	{
		$this->definitions[$name] = $definition;
		if ($singleton || is_object($definition)) {
			$this->singletons[$name] = null;
		} else {
			unset($this->singletons[$name]);
		}
		return $this;
	}

	public function setSingleton($name, $definition)
	{
		return $this->set($name, $definition, true);
	}

	public function get($name)
	{
		if (!isset($this->definitions[$name])) {
			throw new \InvalidArgumentException("对象名称未注册: {$name}");
		}
        $params = func_get_args();
        array_shift($params);
		if (isset($this->singletons[$name])) {
			if (is_null($this->singletons[$name])) {
				$definition = $this->definitions[$name];
                $this->singletons[$name] = (is_callable($definition) ? call_user_func_array($definition, $params) : $definition);
			}
            return $this->singletons[$name];
		} else {
            $definition = $this->definitions[$name];
            return (is_callable($definition) ? call_user_func_array($definition, $params) : $definition);
        }
	}

    public function has($name)
    {
        return isset($this->definitions[$name]);
    }
}
