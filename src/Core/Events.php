<?php

namespace Core;

use Core\Event\Event;

/**
 * 类级别的事件处理
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core
 */
class Events
{
    private static $events = [];

    public static function on($className, $name, $handler, $data = null, $append = true)
    {
        if (!isset(self::$events[$className])) {
            self::$events[$className] = [];
        }
        if ($append || empty(self::$events[$className][$name])) {
            self::$events[$className][$name][] = [$handler, $data];
        } else {
            array_unshift(self::$events[$className][$name], [$handler, $data]);
        }
        return true;
    }

    public static function off($className, $name, $handler = null)
    {
        if (empty(self::$events[$className][$name])) {
            return false;
        }
        if (null === $handler) {
            unset(self::$events[$className][$name]);
            return true;
        } else {
            $removed = false;
            foreach (self::$events[$className][$name] as $key => $value) {
                if ($value[0] === $handler) {
                    unset(self::$events[$className][$name][$key]);
                    $removed = true;
                }
            }
            if ($removed) {
                self::$events[$className][$name] = array_values(self::$events[$className][$name]);
            }
            return $removed;
        }
    }

    public static function trigger($className, $name, Event $event = null)
    {
        if (!empty(self::$events[$className][$name])) {
            if ($event === null) {
                $event = new Event();
            }
            $event->setName($name);
            if ($event->getSender() === null) {
                $event->setSender($className);
            }
            $event->setHandled(false);
            foreach (self::$events[$className][$name] as $handler) {
                $event->setData($handler[1]);
                call_user_func($handler[0], $event);
                if ($event->isHandled()) {
                    return;
                }
            }
        }
    }
}