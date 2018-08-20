<?php
//版本号
define('VERSION', '2.2.1');
//发布时间
define('RELEASE', '20180820');
//用于访问检查
define('IN_APP', TRUE);
//目录分隔符
define('DS', DIRECTORY_SEPARATOR);
//定义当前时间戳常量
define('NOW', isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : time());
//记录开始时间
define('START_TIME', microtime(TRUE));
//内存使用
define('START_MEMORY_USAGE', memory_get_usage());
//框架目录
define('SYSTEM_PATH', __DIR__);

/************* 应用目录 ***************/
//配置文件目录
defined('CONFIG_PATH') or define('CONFIG_PATH', APP_PATH . DS . 'Config');
//语言包目录
defined('LANG_PATH') or define('LANG_PATH', APP_PATH . DS . 'Lang');
//视图模板目录
defined('VIEW_PATH') or define('VIEW_PATH', APP_PATH . DS . 'View');
//发布目录
defined('PUBLIC_PATH') or define('PUBLIC_PATH', realpath(dirname($_SERVER['SCRIPT_FILENAME'])));

/************* 字符集 ****************/
defined('CHARSET') or define('CHARSET', 'utf-8');

/************* 消息码 ****************/
defined('MSG_NONE') or define('MSG_NONE', 0); // 无提示(默认)
defined('MSG_OK') or define('MSG_OK', 0); // 提示成功
defined('MSG_ERR') or define('MSG_ERR', 1); // 提示失败
defined('MSG_NO_LOGIN') or define('MSG_NO_LOGIN', 2); // 尚未登录
defined('MSG_REDIRECT') or define('MSG_REDIRECT', 3); // 跳转
