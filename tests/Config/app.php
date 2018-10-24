<?php
return [
    // 数据库配置
    'database' => [
        'default' => [
            'slow_log' => 0,
            'prefix' => '',
            'charset' => 'utf8',
            'timeout'  => 3,
            'write' => [
                'dsn' => "mysql:host=127.0.0.1;port=3306;dbname=test;charset=utf8",
                'username' => 'root',
                'password' => '',
                'pconnect' => false,
            ],
            'read' => [
                'dsn' => "mysql:host=127.0.0.1;port=3306;dbname=test;charset=utf8",
                'username' => 'root',
                'password' => '',
                'pconnect' => false,
            ]
        ],
    ],
];