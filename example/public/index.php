<?php

//应用代码路径
define('APP_PATH', dirname(__DIR__) . '/app/');
//运行时数据目录
define('DATA_PATH', dirname(__DIR__) . '/data/');

require dirname(dirname(__DIR__)) . '/src/App.php';

App::setDebug(true);
App::bootstrap();
App::run();
