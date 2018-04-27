<?php
/**
 * 应用配置文件
 */

return [

    // 语言
    'lang' => 'zh_CN',

    // 时区设置，PRC为中国时区
    'timezone' => 'PRC',

    //路由配置
    'router' => [
        'pretty_url' => true,
        'default_route' => 'home/index', //默认路由
    ],

    //缓存配置
    'cache' => [
        'default' => 'redis',
        'redis' => [
            'driver' => \Core\Cache\RedisCache::class,
            'config' => [
                'host' => '127.0.0.1',
                'port' => 6379,
            ],
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
        //默认日志配置
        'default' => [
            // 写到文件日志
            [
                'handler' => \Core\Logger\Handler\FileHandler::class, //日志处理器
                'config' => [
                    'level' => \Core\Logger\Logger::DEBUG, //日志级别
                    'formatter' => \Core\Logger\Formatter\ConsoleFormatter::class,
                    'savepath' => DATA_PATH . '/logs/', //日志保存目录
                    'filesize' => 0, //文件分割大小
                    'filename' => '{level}-{Y}{m}{d}.log',
                ],
            ]
        ],
        // 控制台日志配置
        'console' => [
            // 输出到控制台
            [
                'handler' => \Core\Logger\Handler\ConsoleHandler::class, //日志处理器
                'config' => [
                    'level' => \Core\Logger\Logger::DEBUG, //日志级别
                ],
            ],
            // 写到日志文件
            [
                'handler' => \Core\Logger\Handler\FileHandler::class, //日志处理器
                'config' => [
                    'level' => \Core\Logger\Logger::ERROR, //日志级别
                    'formatter' => \Core\Logger\Formatter\LineFormatter::class,
                    'savepath' => DATA_PATH . '/logs/', //日志保存目录
                    'filesize' => 0, //文件分割大小
                    'filename' => '2.log',
                ],
            ],
            [
                'handler' => \Core\Logger\Handler\FileHandler::class, //日志处理器
                'config' => [
                    'level' => \Core\Logger\Logger::WARN, //日志级别
                    'savepath' => DATA_PATH . '/logs/', //日志保存目录
                    'filesize' => 0, //文件分割大小
                    'filename' => '1.log',
                ],
            ],
        ],
    ],
];
