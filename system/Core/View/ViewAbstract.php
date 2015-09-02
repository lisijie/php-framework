<?php

namespace Core\View;

abstract class ViewAbstract implements ViewInterface
{

    const LAYOUT_CONTENT = '<![CDATA[LAYOUT_CONTENT]]>';
    const LAYOUT_SECTION = '<![CDATA[LAYOUT_SECTION_%s]]>';

    protected $data = array();
    protected $options = array();
    protected $layout = '';
    protected $layoutSections = array();

    public final function __construct(array $options)
    {
        $this->options = $options;
        $this->init();
    }

    /**
     * 初始化方法
     */
    protected function init()
    {

    }

    /**
     * 重置
     *
     * 清除模板变量和布局设置
     */
    public function reset()
    {
        $this->data = array();
        $this->layout = '';
        $this->layoutSections = array();
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
     * @return string
     */
    public function content()
    {
        return static::LAYOUT_CONTENT;
    }

    /**
     * 输出布局下的子模板占位符
     * @param $name
     * @return string
     */
    public function section($name)
    {
        if (isset($this->layoutSections[$name])) {
            return sprintf(static::LAYOUT_SECTION, strtoupper($name));
        }
        return '';
    }

    /**
     * 获取设置项的值
     *
     * @param string $name
     * @return mixed
     */
    public function getOption($name)
    {
        return isset($this->options[$name]) ? $this->options[$name] : null;
    }

    /**
     * 注册模板变量
     *
     * @param $name
     * @param $value
     * @return mixed
     */
    public function assign($name, $value = null)
    {
        if (is_array($name)) {
            $this->data = array_merge($this->data, $name);
        } else {
            $this->data[$name] = $value;
        }
    }

    /**
     * 返回模板变量数据
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * 返回模板文件的路径
     *
     * @param $filename
     * @return string
     */
    public function getViewFile($filename)
    {
        return rtrim($this->getOption('template_dir'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename . $this->getOption('ext');
    }

    /**
     * 渲染模板
     *
     * @param string $filename
     * @return string
     */
    public function render($filename)
    {
        $this->beforeRender();
        if (!$this->layout) {
            return $this->renderFile($this->getViewFile($filename));
        }
        // 渲染布局
        $content = $this->renderFile($this->layout);
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

    protected function beforeRender() {}

    abstract protected function renderFile($_file_);

}
