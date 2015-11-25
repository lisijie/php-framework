<?php
/**
 * 应用配置文件
 */

return array(

    //语言
    'lang' => 'zh_CN',

    //时区设置
    'timezone' => 'PRC',

    //视图模板
    'view' => array(
        'engine' => 'native',
        'options' => array(
            'template_dir' => VIEW_PATH,
            'ext' => '.php',
        ),
    ),

    //路由
    'router' => array(
        'type' => 'simple',
        'default_route' => 'main/main/index', //默认路由
    ),


    //缓存配置
    'cache' => array(
        'default' => 'file',
        'file' => array(
            //key前缀
            'prefix' => '',
            //缓存文件保存目录
            'save_path' => DATA_PATH . 'cache/',
        ),
    ),

    //数据库配置
    'database' => array(
        //默认数据库
        'default' => array(
            //是否开启慢查询日志，0为关闭
            'slow_log' => 0.1,
            //表前缀
            'prefix' => 't_',
            //字符集
            'charset' => 'utf8',
            //写库
            'write' => array(
                'dsn' => "mysql:host=localhost;port=3306;dbname=test;charset=utf8",
                'username' => 'root',
                'password' => '123456',
                'pconnect' => false,
            ),
            //读库，只允许配一个地址，如果是一主多从的话，建议使用haproxy或其他中间件做转发
            'read' => array(
                'dsn' => "mysql:host=localhost;port=3306;dbname=test;charset=utf8",
                'username' => 'root',
                'password' => '123456',
                'pconnect' => false,
            )
        ),
    ),

    //日志设置
    'logger' => array(
        //默认日志
        'default' => array(
            //日志处理器1
            array(
                'level' => 1, //日志级别: 1-8
                'handler' => 'FileHandler', //日志处理器
                'config' => array(
                    'savepath' => DATA_PATH . '/logs/', //日志保存目录
                    'filesize' => 0, //文件分割大小
                    'filename' => '{Y}{m}{d}.log',
                ),
            )
        ),
    ),

);


