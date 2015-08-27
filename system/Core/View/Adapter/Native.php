<?php

namespace Core\View\Adapter;

use Core\View\ViewAbstract;
use Core\View\ViewException;

/**
 * 原生PHP模板
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\View
 */
class Native extends ViewAbstract
{

    protected $funcMap = array();

    /**
     * 注册模板函数
     *
     * @param string $name 函数名
     * @param callable $func 回调函数
     */
    public function registerFunc($name, $func)
    {
        $this->funcMap[$name] = $func;
    }

    /**
     * 渲染单个文件
     *
     * @param $_file_
     * @return string
     */
    protected function renderFile($_file_)
    {
        ob_start();
        @extract($this->data, EXTR_OVERWRITE);
        include $_file_;
        return ob_get_clean();
    }

    public function content()
    {
        echo parent::content();
    }

    public function section($name)
    {
        echo parent::section($name);
    }

    public function __call($method, $args)
    {
        if (isset($this->funcMap[$method])) {
            return call_user_func_array($this->funcMap[$method], $args);
        }
        throw new ViewException(__CLASS__ . '::' . $method . ' 方法不存在!');
    }
}