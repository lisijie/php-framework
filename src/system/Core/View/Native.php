<?php

namespace Core\View;

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

    public function render($filename, $data = array())
    {
        ob_start();
        $data = array_merge($this->data, $data);
        $tplFile = $this->getViewFile($filename);
        @extract($data, EXTR_OVERWRITE);
        include $tplFile;
        $content = ob_get_clean();
        return $content;
    }

    protected function layout($filename)
    {
        echo $this->render($filename);
    }

    protected function getViewFile($filename)
    {
        return rtrim($this->getOption('template_dir'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename . $this->getOption('ext');
    }

    public function __call($method, $args)
    {
        if (isset($this->funcMap[$method])) {
            return call_user_func_array($this->funcMap[$method], $args);
        }
        throw new ViewException(__CLASS__ . '::' . $method . ' 方法不存在!');
    }
}