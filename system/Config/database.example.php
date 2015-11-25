<?php
/**
 * 数据库配置示例
 *
 * @author lisijie <lsj86@qq.com>
 * @package config
 */

return array(

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
            'dsn' => "mysql:host=192.168.1.10;port=3306;dbname=test;charset=utf8",
            'username' => 'root',
            'password' => '123456',
            'pconnect' => false,
        ),
        //读库，只允许配一个地址，如果是一主多从的话，建议使用haproxy或其他中间件做转发
        'read' => array(
            'dsn' => "mysql:host=192.168.1.11;port=3306;dbname=test;charset=utf8",
            'username' => 'root',
            'password' => '123456',
            'pconnect' => false,
        )
    ),

    //用户中心
    'user' => array(
	    //是否开启慢查询日志，0为关闭
	    'slow_log' => 0.1,
        //表前缀
        'prefix' => 't_',
        //字符集
        'charset' => 'utf8',
        //写库
        'write' => array(
            'dsn' => "mysql:host=192.168.1.20;port=3306;dbname=test;charset=utf8",
            'username' => 'root',
            'password' => '123456',
            'pconnect' => false,
        ),
        //读库，只允许配一个地址，如果是一主多从的话，建议使用haproxy或其他中间件做转发
        'read' => array(
            'dsn' => "mysql:host=192.168.1.21;port=3306;dbname=test;charset=utf8",
            'username' => 'root',
            'password' => '123456',
            'pconnect' => false,
        )
    ),
);