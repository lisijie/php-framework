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
 * @param string $uri
 * @param array $params 参数
 * @return string
 */
function URL($uri = '', $params = array())
{
    return App::getRouter()->makeUrl($uri, $params);
}

/**
 * 语言包解析
 */
function L($lang, $params = array())
{
    return App::lang($lang, $params);
}
