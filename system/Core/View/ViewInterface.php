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

    /**
     * 获取设置项的值
     *
     * @param string $name
     * @return mixed
     */
    public function getOption($name);

    /**
     * 渲染模板
     *
     * @param $filename
     * @return string
     */
    public function render($filename);

    /**
     * 注册模板变量
     *
     * @param $name
     * @param $value
     * @return mixed
     */
    public function assign($name, $value = null);

    /**
     * 返回模板变量数据
     *
     * @return array
     */
    public function getData();

    /**
     * 返回模板文件路径
     *
     * @param string $filename
     * @return mixed
     */
    public function getViewFile($filename);

    public function setLayout($filename);

    public function setLayoutSection($name, $filename);

    public function reset();
}