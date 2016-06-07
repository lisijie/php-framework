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

    /**
     * 在布局模板中输出页面主体
     */
    public function content()
    {
        echo parent::content();
    }

    /**
     * 输出布局下的子模板占位符
     */
    public function section($name)
    {
        echo parent::section($name);
    }

    /**
     * 渲染页面组件
     *
     * @param string $tplFile 组件模板文件地址
     * @param array $data 模板变量
     * @param bool $return 是否返回结果, false 表示直接输出
     * @return string
     */
    public function widget($tplFile, $data = array(), $return = false)
    {
        $oldData = $this->data;
        $this->data = $data;
        $result = $this->renderFile($this->getViewFile($tplFile));
        $this->data = $oldData;
        if ($return) {
            return $result;
        }
        echo $result;
    }

    /**
     * 调用注册的模板函数
     *
     * @param string $method 方法名
     * @param array $args 参数
     * @return mixed
     * @throws ViewException
     */
    public function __call($method, $args)
    {
        if (isset($this->funcMap[$method])) {
            return call_user_func_array($this->funcMap[$method], $args);
        }
        throw new ViewException(__CLASS__ . '::' . $method . ' 方法不存在!');
    }
}