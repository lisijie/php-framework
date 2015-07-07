<?php

return array(

	//语言包
	'lang' => 'zh_CN',

	//时区
	'timezone' => 'PRC',

	'view' => array(
		'engine' => 'native',
		'options' => array(
			'template_dir' => VIEW_PATH,
			'ext' => '.php',
		),
	),

	'database' => array(
		//默认数据库
		'default' => array(
			//是否开启调试，开启后会记录SQL的执行信息
			'debug' => false,
			//表前缀
			'prefix' => 't_',
			//字符集
			'charset' => 'utf8',
			//写库
			'write' => array(
				'dsn' => "mysql:host=localhost;port=3306;dbname=test;charset=utf8",
				'username' => 'root',
				'password' => '',
				'pconnect' => false,
			),
			//读库，只允许配一个地址，如果是一主多从的话，建议使用haproxy或其他中间件做转发
			'read' => array(
                'dsn' => "mysql:host=localhost;port=3306;dbname=test;charset=utf8",
                'username' => 'root',
                'password' => '',
                'pconnect' => false,
			)
		)
	),

    //路由
    'router' => array(
        'type' => 'simple',
        'default_route' => 'main/index', //默认路由
    ),

    //SESSION
    'session' => array(
        'type' => 'file',
    ),
);
