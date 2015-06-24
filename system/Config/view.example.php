<?php

return array(

    //使用原生PHP作为模板引擎
	'native' => array(
        //模板目录
		'template_dir' => '',
        //模板文件扩展名
		'ext'          => '.php',
	),

    //使用Smarty模板引擎
	'smarty' => array(
        //模板目录
        'template_dir' => VIEW_PATH,
        //模板配置路径
        'config_dir'   => VIEW_PATH . 'config' . DS,
        //模板编译路径
        'compile_dir'  => DATA_PATH . 'cache/smarty_complied',
        //模板缓存路径
        'cache_dir'    => DATA_PATH . 'cache/smarty_cache',
        //模板文件扩展名
        'ext'          => '.html',
	),
);