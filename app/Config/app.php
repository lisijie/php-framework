<?php

return array(

	// 语言包
	'lang' => 'zh_CN',

	// 时区
	'timezone' => 'PRC',

	// 加密密钥
    'secret_key' => 'app secret key',

    // 视图模版配置
    // 支持以下模版引擎：
    //  - native 使用原生PHP语法作为模版引擎
    //  - smarty 使用smarty模版引擎
	'view' => array(
		'engine' => 'native',
		'options' => array(
			'template_dir' => VIEW_PATH,
			'ext' => '.php',
		),
	),

	// 数据库配置
	// 大型项目中通常会进行分库和读写分离，可在这里配置多个数据库节点
	// 在代码中使用 App::getDb('default') 获取指定节点的DB实例。
	'database' => array(
		// 默认数据库节点
		'default' => array(
			// 是否开启调试，开启后会记录SQL的执行信息
			'debug' => false,
			// 表前缀
			'prefix' => 't_',
			// 字符集
			'charset' => 'utf8',
			// 写库
			'write' => array(
				'dsn' => "mysql:host=localhost;port=3306;dbname=test;charset=utf8",
				'username' => 'root',
				'password' => '',
				'pconnect' => false,
			),
			// 读库，只允许配一个地址，如果是一主多从的话，建议使用haproxy或其他中间件做转发
			'read' => array(
                'dsn' => "mysql:host=localhost;port=3306;dbname=test;charset=utf8",
                'username' => 'root',
                'password' => '',
                'pconnect' => false,
			)
		)
	),

    // 路由配置
    // 支持以下几种路由方式：
    //  - simple 简单路由，使用查询参数进行路由，例如 index.php?r=main/index 表示路由到 MainController::indexAction()
    //  - pathinfo PATH_INFO方式，URL形式如： www.domain.com/index.php/main/index?foo=bar
    //  - rewrite URL重写方式，需要在服务器配置重写规则，然后可在路由配置文件 route.php 进行个性化配置
    'router' => array(
        'type' => 'rewrite',
        'default_route' => 'main/index', //默认路由
    ),

    //SESSION
    'session' => array(
        'type' => 'file',
    ),

    // 日志配置
    // 日志可配置多个实例，用于对不同模块有不同日志记录需求的项目，通常情况下使用一个默认就足够了。
    // 使用 App::getLogger() 不带参数获取的是默认实例。
    // 在没有日志配置的情况下，使用 App::getLogger() 依然可以获取到实例，但是写入的日志不会保存。
    'logger' => array(
        // 默认日志
        'default' => array(
            // 日志处理器1
            array(
                'level' => 1, //日志级别: 1-5
                'handler' => 'FileHandler', //日志处理器
                'config' => array(
                    'savepath' => DATA_PATH . '/logs/', //日志保存目录
                    'filesize' => 0, //文件分割大小
                    'filename' => '{Y}{m}{d}.log',
                ),
            )
        ),
    ),

    // 文件上传设置
    'upload' => array(
        'allow_types' => 'jpg|png|gif',
        'save_path' => PUBLIC_PATH . 'upload/{Y}{m}/',
        'maxsize' => 10240, // 10M
    ),
);
