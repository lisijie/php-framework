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
	 * 头部占位符
	 */
	const TAG_PAGE_HEAD = '<![CDATA[TAG_PAGE_HEAD]]>';

	/**
	 * 尾部占位符
	 */
	const TAG_PAGE_FOOT = '<![CDATA[TAG_PAGE_FOOT]]>';

	/**
	 * 头部位置
	 */
	const POS_HEAD = 1;

	/**
	 * 尾部位置
	 */
	const POS_FOOT = 2;

	/**
	 * 模板变量
	 * @var array
	 */
    protected $data = [];

	/**
	 * 视图设置
	 * @var array
	 */
    protected $options = [];

	/**
	 * 布局模板名称
	 * @var string
	 */
	protected $layout = '';

	/**
	 * 布局子模板信息
	 * @var array
	 */
    protected $layoutSections = [];

	/**
	 * css文件列表
	 * @var array
	 */
	protected $cssFiles = [];

	/**
	 * js文件列表
	 * @var array
	 */
	protected $jsFiles = [];

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
	    $this->jsFiles = [];
	    $this->cssFiles = [];
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
     * 返回页面主体占位符
     * @return string
     */
    public function content()
    {
        return static::LAYOUT_CONTENT;
    }

    /**
     * 返回布局下的子模板占位符
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
	 * 返回头部资源占位符
	 * @return string
	 */
	public function head()
	{
		return static::TAG_PAGE_HEAD;
	}

	/**
	 * 返回尾部资源占位符
	 * @return string
	 */
	public function foot()
	{
		return static::TAG_PAGE_FOOT;
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

	    // 主布局渲染
	    if ($this->layout) {
		    $content = $this->renderFile($this->layout);
		    $content = strtr($content, [self::LAYOUT_CONTENT => $this->renderFile($this->getViewFile($filename))]);
	    } else {
		    $content = $this->renderFile($this->getViewFile($filename));
	    }
	    // 子布局模板渲染
	    if (!empty($this->layoutSections)) {
		    $replace = [];
		    foreach ($this->layoutSections as $name => $file) {
			    $replace[sprintf(static::LAYOUT_SECTION, strtoupper($name))] = $this->renderFile($file);
		    }
		    $content = strtr($content, $replace);
	    }
	    // 头部和尾部渲染
	    $replace = [
		    static::TAG_PAGE_HEAD => $this->renderHeadHtml(),
		    static::TAG_PAGE_FOOT => $this->renderFootHtml(),
	    ];

        return strtr($content, $replace);
    }

	/**
	 * 注册CSS文件
	 * @param $url
	 * @param $options
	 * @return bool 成功或失败
	 */
	public function registerCssFile($url, $options = [])
	{
		$attributes = '';
		if (empty($options)) {
			$options['rel'] = 'stylesheet';
		}
		foreach ($options as $key => $value) {
			$attributes .= " {$key}=\"{$value}\"";
		}
		$this->cssFiles[] = '<link '.ltrim($attributes).' href="'.$url.'" />';
		return true;
	}

	/**
	 * 注册JS文件
	 * @param $url
	 * @param $options
	 * @param $head
	 * @return bool 成功或失败
	 */
	public function registerJsFile($url, $options = [], $head = true)
	{
		$attributes = '';
		foreach ($options as $key => $value) {
			$attributes .= " {$key}=\"{$value}\"";
		}
		$string = '<script '.ltrim($attributes).' src="'.$url.'"></script>';
		if ($head) {
			$this->jsFiles[self::POS_HEAD][] = $string;
		} else {
			$this->jsFiles[self::POS_FOOT][] = $string;
		}
		return true;
	}

	/**
	 * 渲染头部资源文件
	 * @return string
	 */
	protected function renderHeadHtml()
	{
		$lines = [];
		if (!empty($this->cssFiles)) {
			$lines[] = implode("\r\n", $this->cssFiles);
		}
		if (!empty($this->jsFiles[self::POS_HEAD])) {
			$lines[] = implode("\r\n", $this->jsFiles[self::POS_HEAD]);
		}
		return empty($lines) ? '' : implode("\r\n", $lines) . "\r\n";
	}

	/**
	 * 渲染尾部资源文件
	 * @return string
	 */
	protected function renderFootHtml()
	{
		$lines = [];
		if (!empty($this->jsFiles[self::POS_FOOT])) {
			$lines[] = implode("\r\n", $this->jsFiles[self::POS_FOOT]);
		}
		return empty($lines) ? '' : implode("\r\n", $lines) . "\r\n";
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
