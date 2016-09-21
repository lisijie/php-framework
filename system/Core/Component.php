<?php
namespace Core;

use Core\Event\Event;

/**
 * 组件类
 * @package Core
 */
class Component extends Object
{
	/**
	 * 保存当前对象各个事件对应的处理器列表
	 * @var array
	 */
	private $events = [];

	/**
	 * 添加一个事件处理器到某个事件
	 * 
	 * @param string $name 事件名称
	 * @param callable $handler 事件处理器
	 * @param null $data 附加数据
	 * @param bool|true $append 追加到末尾
	 * @return bool
	 */
	public function on($name, $handler, $data = null, $append = true)
	{
		if ($append || empty($this->events[$name])) {
			$this->events[$name][] = [$handler, $data];
		} else {
			array_unshift($this->events[$name], [$handler, $data]);
		}
		return true;
	}

	/**
	 * 移除事件处理器
	 * 
	 * @param string $name 事件名称
	 * @param null $handler 需要移除的事件处理器，默认移除所有
	 * @return bool
	 */
	public function off($name, $handler = null)
	{
		if (empty($this->events[$name])) {
			return false;
		}
		if (null === $handler) {
			unset($this->events[$name]);
			return true;
		} else {
			$removed = false;
			foreach ($this->events[$name] as $key => $value) {
				if ($value[0] === $handler) {
					unset($this->events[$name][$key]);
					$removed = true;
				}
			}
			if ($removed) {
				$this->events[$name] = array_values($this->events[$name]);
			}
			return $removed;
		}
	}

	/**
	 * 触发事件
	 * 
	 * @param $name
	 * @param Event|null $event
	 */
	public function trigger($name, Event $event = null)
	{
		if (!empty($this->events[$name])) {
			if ($event === null) {
				$event = new Event();
			}
			$event->setName($name);
			if ($event->getSender() === null) {
				$event->setSender($this);
			}
			$event->setHandled(false);
			foreach ($this->events[$name] as $handler) {
				$event->setData($handler[1]);
				call_user_func($handler[0], $event);
				if ($event->isHandled()) {
					return;
				}
			}
		}
	}
}