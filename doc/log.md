# 日志

## 配置

日志在 `app.php` 配置文件中配置，如 

```php
...
'logger' => [
    'default' => [
        [
            'class' => \Core\Logger\Handler\ConsoleHandler::class,
            'config' => [
                'level' => \Core\Logger\Logger::DEBUG,
            ],
        ],
        [
            'class' => \Core\Logger\Handler\FileHandler::class,
            'config' => [
                'level' => \Core\Logger\Logger::WARN,
                'formatter' => \Core\Logger\Formatter\JsonFormatter::class,
                'savepath' => DATA_PATH . '/logs/',
                'filesize' => 0,
                'filename' => '{level}-{Y}{m}{d}.log',
            ],
        ]
    ],
    'channel2' => [
        [
            'class' => \Core\Logger\Handler\DbHandler::class,
            'config' => [
                'level' => \Core\Logger\Logger::ERROR,
                'dsn' => 'mysql:host=localhost;port=3306;dbname=test;charset=utf8',
                'username' => 'root',
                'password' => '',
                'table' => 'sys_log',
            ],
        ]
    ],
],
...
```

上面的日志配置了 `default`、`channel2` 两个通道，`default` 通道配置了2个日志处理器，分别是 ConsoleHandler 和 FileHandler，ConsoleHandler 用于在控制台输出日志内容，而 FileHandler 则是将日志写到磁盘文件中。每个日志处理器的 `config` 数组内容将会传给对应 handler 的构造方法，不同的 handler 所需的配置项会有一些差异，但有几个配置项是固定的：

* level：日志的级别
* formatter：格式化器，用于格式化日志内容的格式，如JSON格式的JsonFormatter。
* date_format：日志的日期格式

## 使用

使用 App::logger() 获取对象，默认是返回 default 通道的日志对象，可以使用 App::logger('channel2') 获取 channel2 通道的日志对象。

```php
App::logger()->debug('debug log...');
App::logger()->info("info log...");
App::logger()->warn("warn log...");
App::logger()->error("error log...");
App::logger()->fatal("fatal log...");
```

输出

```
2017-09-22 14:29:47 [console] [D] [DemoCommand.php:11] debug log...
2017-09-22 14:29:47 [console] [I] [DemoCommand.php:12] info log...
2017-09-22 14:29:47 [console] [W] [DemoCommand.php:13] warn log...
2017-09-22 14:29:47 [console] [E] [DemoCommand.php:14] error log...
2017-09-22 14:29:47 [console] [F] [DemoCommand.php:15] fatal log...
```