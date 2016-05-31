<?php

namespace Core\View;

/**
 * 视图模板抽象类
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\View
 */
abstract class ViewAbstract implements ViewInterface
{
	/**
	 * 布局模板中内容主体的占位符
	 */
    const LAYOUT_CONTENT = '<![CDATA[LAYOUT_CONTENT]]>';

	/**
	 * 布局模板中子模板的占位符
	 */
    const LAYOUT_SECTION = '<![CDATA[LAYOUT_SECTION_%s]]>';

	/**
	 * 模板变量
	 * @var array
	 */
    protected $data = array();

	/**
	 * 视图设置
	 * @var array
	 */
    protected $options = array();

	/**
	 * 布局模板名称
	 * @var string
	 */
	protected $layout = '';

	/**
	 * 布局子模板信息
	 * @var array
	 */
    protected $layoutSections = array();

	/**
	 * 构造函数
	 * @param array $options 配置选项，不同的模板引擎有不同的选项
	 */
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
        $this->data = [];
        $this->layout = '';
        $this->layoutSections = [];
    }

    /**
     * 设置布局模板
     *
     * @param string $filename
     * @throws ViewException
     * @return bool
     */
    public function setLayout($filename)
    {
        $filename = $this->getViewFile($filename);
        if (!is_file($filename)) {
            throw new ViewException("布局模板不存在: {$filename}");
        }
        $this->layout = $filename;
	    return true;
    }

    /**
     * 设置布局的子模板
     *
     * 用于复杂的布局，例如将页面的头部、底部分别独立出来，用法：
     *  1. 在控制器中使用 setLayoutSection('标识名称', '模板文件') 设置
     *  2. 在 layout 模板使用 $this->section('标识名称') 填充
     *
     * @param string $name
     * @param string $filename
     * @throws ViewException
     * @return bool
     */
    public function setLayoutSection($name, $filename)
    {
        $filename = $this->getViewFile($filename);
        if (!is_file($filename)) {
            throw new ViewException("布局模板不存在: {$filename}");
        }
        $this->layoutSections[$name] = $filename;
	    return true;
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
     * @param string $filename 模板文件名
     * @param array $data 模板变量
     * @return string
     */
    public function render($filename, array $data = [])
    {
	    $this->data = $data;
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

	/**
	 * 渲染模板前的操作
	 */
    protected function beforeRender() {}

	/**
	 * 渲染模板
	 * @param string $_file_ 模板文件名_
	 * @return string
	 */
    abstract protected function renderFile($_file_);

}
