# 我的PHP框架 #

一个优雅、简洁、高效的PHP框架，用于快速开发各种类型的PHP项目，零学习成本。

### 特点

- 符合PSR编码规范，支持composer
- 简单的路由配置
- 清晰的代码结构，方便开发可维护性好的大型项目

### 实践指南

###### 1. 目录结构

一个基本的应用目录结构如下：

	|- app 应用目录
	|  |- Command 命令行脚本控制器（可选）
	|  |- Config 配置文件
	|  |- Controller Web控制器
	|  |- Exception 自定义异常类型和异常处理器 (可选)
	|  |- Model 数据模型，提供数据的读写接口，一张表对应一个类 (可选)
	|  |- View 视图模板文件
	|- data 运行时数据目录（日志、缓存文件等）
	|- public 发布目录
	|- system 框架目录
	|- vendor composer第三方包目录

在开发功能简单的小型项目时，app目录下保留必要的目录即可，所有业务逻辑和数据库读写操作可以直接在Controller里面进行。

对于稍微大点的项目，以上的开发方式可能会让代码变得难以维护，通常会在数据库之上再增加一个Model层，将每个表的读写操作进行封装，外部控制器不再直接使用SQL查询，未来进行分库分表甚至更换数据库时，直接修改Model层的代码即可。

对于较大型的项目，或者需要提供多端接口的应用（如手机APP），建议在 Controller 和 Model 之间再增加一个 Service 层，用于封装业务逻辑，便于重用业务代码，最终调用关系为 Controller -> Service -> Model。

###### 2. 命令行脚本

很多项目都会有在命令行模式下执行PHP脚本的需求，例如结合crontab做定时数据统计、数据清理等。在本框架中，命令行脚本控制器统一放在Command目录下，需要继承自 Core\CliController，如果需要参数，则必须在方法中声明。示例代码：

```php
<?php
namespace App\Command;

use Core\CliController as Controller;

class DemoController extends Controller
{
    public function testAction($name)
    {
        $this->stdout("hello, {$name}\n");
    }
}
```

执行命令行脚本方法：

可以直接在终端使用 
> php index.php 路由地址 参数1 参数2... 

例如以上代码的执行命令为

```Bash
[demo@localhost public]$ php index.php demo/test world
hello, world
```

如果你需要定时执行以上命令，把它添加到crontab配置中即可。

另一个更好的方式是单独写一个命令行脚本的入口文件，放到public以外的目录，并加上可执行权限，然后像执行二进制程序一样去执行它。

```php
#!/usr/local/php/bin/php
<?php
define('APP_PATH',  dirname(__DIR__) .'/app/');
define('DATA_PATH', dirname(__DIR__) .'/data/');

define('DEBUG', true);
define('RUN_MODE', 'dev');

require dirname(__DIR__) .'/system/App.php';

App::bootstrap();
App::run();
```

把以上代码保存到名为 app 的文件中，然后加上可执行权限。之后就可以直接使用 ./app 运行了。几处路径请自行修改。

### 服务器配置

##### Nginx

要启用Url重写，请在Nginx网站配置中增加以下配置：

	location / {
	    try_files $uri $uri/ /index.php?$args;
	}


### 演示项目

- [www.lampnotes.com](http://www.lampnotes.com "Web开发技术分享平台") (Web开发技术分享平台)
