# 日志

## 配置

示例

```php
//日志设置
'logger' => [
    //默认日志配置
    'default' => [
        // 写到文件日志
        [
            'level' => \Core\Logger\Logger::WARN, //日志级别
            'handler' => \Core\Logger\Handler\FileHandler::class, //日志处理器
            'formatter' => \Core\Logger\Formatter\JsonFormatter::class,
            'config' => [
                'savepath' => DATA_PATH . '/logs/', //日志保存目录
                'filesize' => 0, //文件分割大小
                'filename' => '{level}-{Y}{m}{d}.log',
            ],
        ]
    ],
    // 控制台日志配置
    'console' => [
        // 输出到控制台
        [
            'level' => \Core\Logger\Logger::DEBUG, //日志级别
            'handler' => \Core\Logger\Handler\ConsoleHandler::class, //日志处理器
            'formatter' => \Core\Logger\Formatter\ConsoleFormatter::class,
            'config' => [],
        ],
        // 写到日志文件
        [
            'level' => \Core\Logger\Logger::WARN, //日志级别
            'handler' => \Core\Logger\Handler\FileHandler::class, //日志处理器
            'formatter' => \Core\Logger\Formatter\JsonFormatter::class,
            'config' => [
                'savepath' => DATA_PATH . '/logs/', //日志保存目录
                'filesize' => 0, //文件分割大小
                'filename' => '{level}-{Y}{m}{d}.log',
            ],
        ]
    ],
],
```

## 使用

使用 App::logger() 获取对象。

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