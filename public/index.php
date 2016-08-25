<?php

//应用代码路径
define('APP_PATH',  dirname(__DIR__) .'/app/');
//运行时数据目录
define('DATA_PATH', dirname(__DIR__) .'/data/');

require dirname(__DIR__) .'/system/App.php';

if (!\Core\Environment::isProduction()) {
	define('DEBUG', true);
}
App::bootstrap();
App::run();
