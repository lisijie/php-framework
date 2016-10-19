# 一个轻量级PHP框架 #

一个优雅、简洁、高效的PHP框架，用于快速开发扩展性强、可维护性强的PHP项目，零学习成本。

## 安装说明

只安装框架：

```bash
$ composer require lisijie/framework
```

创建一个带有基本应用程序模板的项目**（推荐）**：

```bash
$ composer create-project lisijie/framework-app
```

## 使用说明

### 一、目录结构

一个基本的应用目录结构如下：

	|- app 应用目录
	|  |- Command 命令行脚本控制器（可选）
	|  |- Config 配置文件
	|  |- Controller Web控制器
	|  |- Exception 自定义异常类型和异常处理器（可选）
	|  |- Model 数据模型，提供数据的读写接口，一张表对应一个类（可选）
	|  |- Service Service模块，封装业务逻辑，操作Model（可选）
	|  |- View 视图模板文件
	|- data 运行时数据目录（日志、缓存文件等）
	|- public 发布目录，存放外部可访问的资源和index.php入口文件
	|- system 框架目录
	|- vendor composer第三方包目录

框架使用符合PSR规范的自动加载机制，可以在 `app` 目录下创建其他包。如工具包 `Util`，使用的命名空间为 `App\Util`。

### 二、配置文件

配置文件统一放置在 `app/Config` 目录下，其下又分了 development、testing、pre_release、production 子目录，分别用于开发环境、测试环境、预发布环境、生产环境的配置。放在 Config 根目录下的配置文件表示全局配置，如路由配置。放在环境目录下的为差异配置。加载方式是：首先读取全局配置，然后读取差异配置，然后将差异配置的配置项覆盖到全局配置。

配置的获取方式为：

```php
\App::config()->get('app', 'varname', 'default');
```

其中 app 表示配置文件名，varname 表示配置项， default 表示当不存在该配置项时，使用它作为返回值。

### 三、命令行脚本

很多项目都会有在命令行模式下执行PHP脚本的需求，例如结合crontab做定时数据统计、数据清理等。在本框架中，命令行脚本控制器统一放在Command目录下，需要继承自 Core\Command，如果需要参数，则必须在方法中声明。示例代码：

```php
<?php
namespace App\Command;

use Core\Command;

class DemoCommand extends Command
{
    public function testAction($name)
    {
        $this->stdout("hello, {$name}\n");
    }
}
```

执行命令行脚本方法：

直接在终端使用以下命令执行 

```bash
php index.php 路由地址 参数1 参数2... 
```

例如以上代码的执行命令为

```Bash
[demo@localhost public]$ php index.php demo/test world
hello, world
```

如果你需要定时执行以上命令，把它添加到crontab配置中即可。

### 四、控制器

控制器位于 `app\Controller` 目录下，可以在该目录下创建多个包，每个控制器类的名称以 `Controller` 为后缀。如：MainController.php。一个控制器类大致如下：

```php
<?php
namespace App\Controller;

class MainController extends Controller
{

    public function init()
    {
        // 初始化方法
    }

    public function indexAction()
    {
        $this->assign('foo', 'bar');
        return $this->display();
    }

}
```

每个动作方法必须加上 `Action` 后缀。对应的访问地址为：http://domain.com/?r=main/index。其中 `r` 参数为路由变量，路由地址是 `main/index`。路由地址为全小写形式，多个单词将被转换成`小写+减号`的形式，例如 UserInfoController::indexAction() 对应的路由地址为 `user-info/index`。为了让url看起来更加美观，建议在web服务器配置url rewrite。


### 五、服务器配置

#### Nginx

要启用Url重写，请在Nginx网站配置中增加以下配置：

	location / {
	    try_files $uri $uri/ /index.php?$args;
	}

