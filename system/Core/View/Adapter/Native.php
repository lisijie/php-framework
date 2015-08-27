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

    const LAYOUT_CONTENT = '<![CDATA[LAYOUT_CONTENT]]>';
    const LAYOUT_SECTION = '<![CDATA[LAYOUT_SECTION_%s]]>';

    protected $funcMap = array();
    protected $layout = '';
    protected $layoutSections = array();

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
     * 设置布局模板
     *
     * @param $filename
     * @throws ViewException
     */
    public function setLayout($filename)
    {
        $filename = $this->getViewFile($filename);
        if (!is_file($filename)) {
            throw new ViewException("布局模板不存在: {$filename}");
        }
        $this->layout = $filename;
    }

    /**
     * 设置布局的子模板
     *
     * 用于复杂的布局，例如将页面的头部、底部分别独立出来，用法：
     *  1. 在控制器中使用 setLayoutSection('标识名称', '模板文件') 设置
     *  2. 在 layout 模板使用 $this->section('标识名称') 填充
     *
     * @param string name
     * @param string $filename
     * @throws ViewException
     */
    public function setLayoutSection($name, $filename)
    {
        $filename = $this->getViewFile($filename);
        if (!is_file($filename)) {
            throw new ViewException("布局模板不存在: {$filename}");
        }
        $this->layoutSections[$name] = $filename;
    }

    /**
     * 输出页面主体占位符
     */
    public function content()
    {
        echo static::LAYOUT_CONTENT;
    }

    /**
     * 输出布局下的子模板占位符
     * @param $name
     */
    public function section($name)
    {
        if (isset($this->layoutSections[$name])) {
            echo sprintf(static::LAYOUT_SECTION, strtoupper($name));
        }
    }

    /**
     * 渲染模板
     *
     * @param string $filename
     * @param array $data
     * @return string
     */
    public function render($filename, $data = array())
    {
        $this->data = array_merge($this->data, $data);
        if (!$this->layout) {
            return $this->renderFile($this->getViewFile($filename));
        }

        // 渲染布局
        $content = $this->renderFile($this->layout, $data);
        $replace = array(
            static::LAYOUT_CONTENT => $this->renderFile($this->getViewFile($filename))
        );
        if (!empty($this->layoutSections)) {
            foreach ($this->layoutSections as $name => $file) {
                $replace[sprintf(static::LAYOUT_SECTION, strtoupper($name))] = $this->renderFile($file);
            }
        }

        return strtr($content, $replace);
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
     * 返回模板文件的路径
     *
     * @param $filename
     * @return string
     */
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