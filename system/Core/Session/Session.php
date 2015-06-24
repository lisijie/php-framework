<?php
/**
 * SESSION操作
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core
 */

namespace Core\Session;

use Core\Session\Handler\HandlerInterface;

class Session
{
    /**
     * session 处理器
     *
     * @var HandlerInterface;
     */
    protected $handler;

    /**
     * session是否激活
     *
     * @var bool
     */
    protected $isActive = false;

    /**
     * session cookie参数
     * @var array
     */
    protected $cookieParams = array(
        'httponly' => true,
    );

    protected $flashParam = '__flash__';

    /**
     * 设置session处理器
     *
     * @param HandlerInterface $handler
     */
    public function setHandler($handler)
    {
        if ($handler instanceof \SessionHandlerInterface && $handler instanceof HandlerInterface) {
            throw new \InvalidArgumentException('session处理器必须实现SessionHandlerInterface或Core\\Session\\Handler\\HandlerInterface接口');
        }
        $this->handler = $handler;
    }

    /**
     * 设置session文件保存目录
     *
     * @param string $path
     * @return string
     * @throws \InvalidArgumentException
     */
    public function setSavePath($path)
    {
        if (is_dir($path)) {
            return session_save_path($path);
        }
        throw new \InvalidArgumentException("session保存目录无效: {$path}");

    }

    /**
     * 获取session文件保存目录
     *
     * @return string
     */
    public function getSavePath()
    {
        return session_save_path();
    }

    /**
     * 设置cookie参数
     *
     * @param array $params
     */
    public function setCookieParams(array $params)
    {
        $this->cookieParams = $params;
    }

    /**
     * 获取cookie参数
     *
     * @return array
     */
    public function getCookieParams()
    {
        return $this->cookieParams;
    }

    /**
     * 开启session
     */
    public function start()
    {
        if ($this->isActive()) {
            return;
        }
        $this->isActive = true;

        if ($this->handler) {
            session_set_save_handler(
                array($this->handler, 'open'),
                array($this->handler, 'close'),
                array($this->handler, 'read'),
                array($this->handler, 'write'),
                array($this->handler, 'destroy'),
                array($this->handler, 'gc')
            );
            register_shutdown_function(array($this, 'close'));
        }

        $cookieParams = array_merge(session_get_cookie_params(), $this->cookieParams);
        session_set_cookie_params($cookieParams['lifetime'], $cookieParams['path'], $cookieParams['domain'], $cookieParams['secure'], $cookieParams['httponly']);

        session_start();

        $this->updateFlashCounters();
    }

    /**
     * session是否激活
     *
     * @return bool
     */
    public function isActive()
    {
        if (function_exists('session_status')) {
            return session_status() == PHP_SESSION_ACTIVE;
        }
        return $this->isActive;
    }

    /**
     * 获取session id
     *
     * @return string
     */
    public function getId()
    {
        return session_id();
    }

    /**
     * 获取session名称
     *
     * @return string
     */
    public function getName()
    {
        return session_name();
    }

    /**
     * 设置session名称
     *
     * @param $name
     * @return string
     */
    public function setName($name)
    {
        return session_name($name);
    }

    /**
     * 获取session值
     *
     * @param string $name
     * @param mixed $default 默认值
     * @return null
     */
    public function get($name, $default = null)
    {
        $this->start();
        return isset($_SESSION[$name]) ? $_SESSION[$name] : $default;
    }

    /**
     * 设置session
     *
     * @param string $name
     * @param mixed $value
     */
    public function set($name, $value)
    {
        $this->start();
        $_SESSION[$name] = $value;
    }

    /**
     * 删除session
     *
     * @param string $name 名称
     * @return null 返回被删除的值
     */
    public function remove($name)
    {
        $this->start();
        if (isset($_SESSION)) {
            $value = $_SESSION[$name];
            unset($_SESSION[$name]);
            return $value;
        }
        return null;
    }

    /**
     * 删除所有session数据
     */
    public function removeAll()
    {
        if ($this->isActive()) {
            session_unset();
        }
    }

    /**
     * 检查session是否存在
     *
     * @param string $name
     * @return bool
     */
    public function has($name)
    {
        return isset($_SESSION[$name]);
    }

    /**
     * 设置flash数据
     *
     * 关于$removeAfterAccess:
     *  - true: 数据被访问后，将表示为删除，下次请求会清除，如果数据一直没被访问，将不清除
     *  - false: 不管数据有没被访问，下次请求后，都被清除
     *
     * @param string $name 名称
     * @param mixed $value 值
     * @param bool $removeAfterAccess 是否访问后删除
     */
    public function setFlash($name, $value = null, $removeAfterAccess = true)
    {
        $this->start();
        $counters = $this->get($this->flashParam, array());
        $counters[$name] = $removeAfterAccess ? -1 : 0;
        $_SESSION[$name] = $value;
        $_SESSION[$this->flashParam] = $counters;
    }

    /**
     * 获取flash数据
     *
     * @param string $name 名称
     * @param null $default 默认值
     * @param bool $delete 是否立刻删除
     * @return null
     */
    public function getFlash($name, $default = null, $delete = false)
    {
        $counters = $this->get($this->flashParam, array());
        if (isset($counters[$name])) {
            $value = $this->get($name, $default);
            if ($delete) {
                $this->removeFlash($name);
            } elseif ($counters[$name] < 0) {
                $counters[$name] = 1;
                $_SESSION[$this->flashParam] = $counters;
            }
            return $value;
        }
        return $default;
    }

    /**
     * 删除flash数据
     *
     * 删除并返回被删除的值，如果不存在返回false
     *
     * @param string $name
     * @return bool|null
     */
    public function removeFlash($name)
    {
        $this->start();
        $counters = $this->get($this->flashParam, array());
        if (isset($counters[$name])) {
            $value = isset($_SESSION[$name]) ? $_SESSION[$name] : null;
            unset($_SESSION[$name], $counters[$name]);
            $_SESSION[$this->flashParam] = $counters;
            return $value;
        }
        return false;
    }

    /**
     * 删除所有flash数据
     */
    public function removeAllFlash()
    {
        $this->start();
        $counters = $this->get($this->flashParam, array());
        foreach ($counters as $name => $count) {
            unset($_SESSION[$name]);
        }
        unset($_SESSION[$this->flashParam]);
    }

    /**
     * 检查flash数据是否存在
     *
     * @param string $name
     * @return bool
     */
    public function hasFlash($name)
    {
        return $this->getFlash($name) === null;
    }

    /**
     * 更新flash数据计数器，清除过期数据
     */
    protected function updateFlashCounters()
    {
        $counters = $this->get($this->flashParam, array());
        if (is_array($counters)) {
            foreach ($counters as $name => $count) {
                if ($count == 0) {
                    $counters[$name]++;
                } elseif ($count > 0) {
                    unset($counters[$name], $_SESSION[$name]);
                }
            }
            $_SESSION[$this->flashParam] = $counters;
        } else {
            unset($_SESSION[$this->flashParam]);
        }
    }

    /**
     * 关闭session
     */
    public function close()
    {
        @session_write_close();
    }

    /**
     * 销毁session回话
     */
    public function destroy()
    {
        if ($this->isActive()) {
            @session_unset();
            @session_destroy();
        }
    }
}
