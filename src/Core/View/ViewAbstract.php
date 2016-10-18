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
     * 配置信息
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
        // 静态资源基础URL，需要以斜杠结尾
        if (!isset($options['static_url'])) {
            $options['static_url'] = '/';
        }
        // 静态资源版本号,如: 1.0.0
        if (!isset($options['static_version'])) {
            $options['static_version'] = '';
        }
        // 版本号变量名
        if (!isset($options['static_version_var'])) {
            $options['static_version_var'] = 'v';
        }
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
     * 设置配置项的值
     *
     * @param $name
     * @param $value
     * @return bool
     */
    public function setOption($name, $value)
    {
        $this->options[$name] = $value;
        return true;
    }

    /**
     * 重置
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
     *
     * 在当前的模板对象注册一个CSS文件，用于在控制器中预先设置模板需要依赖的资源文件，或者在模板头部中统一设置引入的资源文件。
     * 后面需要在HTML模板中使用 <?=$this->head() ?> 进行渲染。
     *
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
        $this->cssFiles[] = '<link ' . ltrim($attributes) . ' href="' . $this->getAssetUrl($url) . '" />';
        return true;
    }

    /**
     * 引入CSS文件
     *
     * 用于在模板中引入CSS文件，返回包含css文件的link标签。使用方法：
     * <?=$this->requireCssFile('css/base.css')?>
     *
     * @param $url
     * @param array $options
     * @return string
     */
    public function requireCssFile($url, $options = [])
    {
        $attributes = '';
        if (empty($options)) {
            $options['rel'] = 'stylesheet';
        }
        foreach ($options as $key => $value) {
            $attributes .= "{$key}=\"{$value}\" ";
        }
        return '<link ' . $attributes . 'href="' . $this->getAssetUrl($url) . '" />';
    }

    /**
     * 注册JS文件
     *
     * 在当前的模板对象注册一个JS文件，用于在控制器中预先设置模板需要依赖的资源文件，或者在模板头部中统一设置引入的资源文件。
     * 后面需要在HTML模板中使用 <?=$this->head() ?> 和 <?=$this->foot()?> 进行渲染。
     *
     * @param $url
     * @param $options
     * @param $head
     * @return bool 成功或失败
     */
    public function registerJsFile($url, $options = [], $head = true)
    {
        if ($head) {
            $this->jsFiles[self::POS_HEAD][] = $this->requireJsFile($url, $options);
        } else {
            $this->jsFiles[self::POS_FOOT][] = $this->requireJsFile($url, $options);
        }
        return true;
    }

    /**
     * 引入JS文件
     *
     * 用于在模板中引入JS文件，返回包含JS文件的script标签，在模板中使用方法：
     * <?=$this->requireJsFile('js/base.js')?>
     *
     * @param string $url
     * @param array $options 附加属性
     * @return string
     */
    public function requireJsFile($url, $options = [])
    {
        $attributes = '';
        foreach ($options as $key => $value) {
            $attributes .= "{$key}=\"{$value}\" ";
        }
        return '<script ' . $attributes . 'src="' . $this->getAssetUrl($url) . '"></script>';
    }

    /**
     * 返回资源的实际URL
     *
     * 返回包含基础URL和版本号的资源URL。实际项目中通常把静态资源单独部署到CDN上，并且使用独立的域名进行访问。
     * 但是本地开发环境为了方便调试，通常都是直接访问当前项目下的资源文件，因此需要做统一的处理。
     * 加版本号的作用在于刷新服务器或浏览器对该静态文件的缓存。
     *
     * @param string $url 资源的相对URL
     * @return string
     */
    public function getAssetUrl($url)
    {
        $url = $this->options['static_url'] . ltrim($url, '/');
        if (!empty($this->options['static_version'])) {
            $url .= ((strpos($url, '?') === false) ? '?' : '&') . $this->options['static_version_var'] . '=' . $this->options['static_version'];
        }
        return $url;
    }

    /**
     * 渲染头部资源文件
     *
     * 首先输出的是CSS文件，其次是JS文件。
     *
     * @return string
     */
    private function renderHeadHtml()
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
     *
     * 尾部一般只有JS文件。
     *
     * @return string
     */
    private function renderFootHtml()
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
    protected function beforeRender()
    {
    }

    /**
     * 渲染模板
     * @param string $_file_ 模板文件名_
     * @return string
     */
    abstract protected function renderFile($_file_);

}
