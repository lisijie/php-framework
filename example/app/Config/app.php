<?php
/**
 * 应用配置文件
 */

return [

    // 语言
    'lang' => 'zh_CN',

    // 时区设置，PRC为中国时区
    'timezone' => 'PRC',

    // 视图模板配置，使用原生PHP作为模板引擎
    'view' => [
        'engine' => 'native',
        'options' => [
            'template_dir' => VIEW_PATH,
            'ext' => '.php',
            'static_url' => '/',
            'static_version' => '1.0',
        ],
    ],

    //路由类型配置
    'router' => [
        'type' => 'simple',
        'options' => [
            'default_route' => 'home/index', //默认路由
        ],
    ],

    //缓存配置
    'cache' => [
        'default' => 'file',
        'file' => [
            //key前缀
            'prefix' => '',
            //缓存文件保存目录
            'save_path' => DATA_PATH . 'cache/',
        ],
    ],

    //数据库配置
    'database' => [
        //默认数据库
        'default' => [
            //是否开启慢查询日志，0为关闭
            'slow_log' => 0.1,
            //表前缀
            'prefix' => 't_',
            //字符集
            'charset' => 'utf8',
            //写库
            'write' => [
                'dsn' => "mysql:host=localhost;port=3306;dbname=test;charset=utf8",
                'username' => 'root',
                'password' => '',
                'pconnect' => false,
            ],
            //读库，只允许配一个地址，如果是一主多从的话，建议使用haproxy或其他中间件做转发
            'read' => [
                'dsn' => "mysql:host=localhost;port=3306;dbname=test;charset=utf8",
                'username' => 'root',
                'password' => '',
                'pconnect' => false,
            ],
        ],
    ],

    //日志设置
    'logger' => [
        //默认日志
        'default' => [
            //日志处理器1
            [
                'level' => 1, //日志级别: 1-8
                'handler' => 'FileHandler', //日志处理器
                'config' => [
                    'savepath' => DATA_PATH . '/logs/', //日志保存目录
                    'filesize' => 0, //文件分割大小
                    'filename' => '{level}-{Y}{m}{d}.log',
                ],
            ]
        ],
    ],
];
