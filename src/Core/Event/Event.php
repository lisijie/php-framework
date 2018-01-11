<?php
namespace Core\Event;

/**
 * 事件类
 * 对于各种不同类型的事件，可以派生出不同的子类。如: DbEvent, MsgEvent
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Event
 */
class Event
{
    /**
     * 事件名称
     * @var string
     */
    private $name = '';

    /**
     * 事件产生对象，在静态方法触发的类全局事件该值为触发的类名字符串
     * @var object|string
     */
    private $sender = null;

    /**
     * 附加数据
     * @var null
     */
    private $data = null;

    /**
     * 已处理标识，设为true的话则忽略掉后面的事件
     * @var bool
     */
    private $handled = false;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return object
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * @param object $sender
     */
    public function setSender($sender)
    {
        $this->sender = $sender;
    }

    /**
     * @return null
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param null $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return boolean
     */
    public function isHandled()
    {
        return $this->handled;
    }

    /**
     * @param boolean $handled
     */
    public function setHandled($handled)
    {
        $this->handled = $handled;
    }
}