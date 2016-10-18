<?php
/**
 * 公共函数库
 *
 * @author lisijie <lsj86@qq.com>
 * @package core
 */


/**
 * 生成URL
 *
 * @param string $route
 * @param array $params 参数
 * @return string
 */
function URL($route = '', $params = [])
{
    return App::router()->makeUrl($route, $params);
}

/**
 * 语言包解析
 * @param string $lang
 * @param array $params
 * @return string
 */
function L($lang, $params = [])
{
    return App::lang($lang, $params);
}
