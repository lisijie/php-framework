<?php

namespace Core\View;

/**
 * 模版引擎接口
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\View
 */
interface ViewInterface
{

    public function getOption($name);

    /**
     * 渲染模板
     *
     * @param $filename
     * @param $data
     * @return string
     */
    public function render($filename, $data = array());

    /**
     * 注册模板变量
     *
     * @param $var
     * @param $value
     * @return mixed
     */
    public function assign($var, $value = null);

    /**
     * 返回模板变量数据
     *
     * @return array
     */
    public function getData();

}