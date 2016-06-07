<?php

return [

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

	// 视图模板配置，使用smarty模板引擎
	'view' => [
		'engine' => 'smarty',
		'options' => [
            'template_dir' => VIEW_PATH, // 模板目录
            'config_dir'   => VIEW_PATH . 'config' . DS, // 模板配置路径
            'compile_dir'  => DATA_PATH . 'cache/smarty_complied', // 模板编译路径
            'cache_dir'    => DATA_PATH . 'cache/smarty_cache', // 模板缓存路径
            'ext'          => '.html', // 模板文件扩展名
			'static_url'   => '/', // 静态资源基础URL
			'static_version' => '1.0', // 静态资源版本号
		],
	],

];