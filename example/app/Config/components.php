<?php

return [
    // 视图模板配置，使用原生PHP作为模板引擎
    'view' => [
        'class' => \Core\View\Native::class,
        'options' => [
            'template_dir' => VIEW_PATH,
            'ext' => '.php',
            'static_url' => '/',
            'static_version' => '1.0',
        ],
    ],

    'cache' => [
        'class' => \Core\Cache\NullCache::class,
        'config' => [],
    ],

    'session' => [

    ]
];