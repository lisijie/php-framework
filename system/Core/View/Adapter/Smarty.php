<?php

namespace Core\View\Adapter;

use Core\View\ViewAbstract;
use Core\View\ViewException;

/**
 * Smarty模版引擎
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\View
 */
class Smarty extends ViewAbstract
{
    private $smarty;

    protected function init()
    {
        if (!isset($this->options['ext'])) {
            $this->options['ext'] = '.html';
        }
        $defaults = array(
            'template_dir' => VIEW_PATH,
            'config_dir' => VIEW_PATH . 'config' . DIRECTORY_SEPARATOR,
            'compile_dir' => DATA_PATH . 'cache/smarty_complied',
            'cache_dir' => DATA_PATH . 'cache/smarty_cache',
        );
        $this->options = array_merge($defaults, $this->options);
        if (!class_exists('\Smarty')) {
            throw new ViewException('Smarty 类不存在，请使用composer安装');
        }
        $this->smarty = new \Smarty();
        foreach ($defaults as $key => $value) {
            $this->smarty->{$key} = $this->options[$key];
        }
        $this->smarty->registerPlugin('function', 'layout_section', array($this, 'section'));
        $this->smarty->registerPlugin('function', 'layout_content', array($this, 'content'));
    }

    public function section($params)
    {
        if (isset($params['name'])) {
            return parent::section($params['name']);
        }
    }

    protected function beforeRender()
    {
        $this->smarty->assign($this->data);
    }

    protected function renderFile($filename)
    {
        return $this->smarty->fetch($filename);
    }

    public function __call($method, $args)
    {
        return call_user_func_array(array($this->smarty, $method), $args);
    }
}
