<?php

namespace Core\View;

abstract class ViewAbstract implements ViewInterface
{
    protected $data = array();
    protected $options = array();

    public function __construct(array $options)
    {
        $this->options = $options;
    }

    public function getOption($name)
    {
        return isset($this->options[$name]) ? $this->options[$name] : null;
    }

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
     * 渲染模板
     *
     * @param $filename
     * @param $data
     * @return string
     */
    abstract function render($filename, $data = array());

}
