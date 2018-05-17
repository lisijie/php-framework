# 路由

默认的路由地址是通过请求中的 `r` 参数来定位的，如

```
http://yourdomain/?r=user/list&page=2
```

对应的控制器方法是 `App\Controller\UserController::listAction()`。通过web服务器的rewrite功能，可以将URL美化为

```php
http://yourdomain/user/list?page=2
```

### 开启URL美化

Apache 服务器在网站根目录创建 .htaccess文件，内容为：

```
<IfModule mod_rewrite.c>
   RewriteEngine on
   RewriteBase /
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteRule ^.*$ index.php?$0 [L]
</IfModule>
```

Nginx 服务器在 server {} 内加入：

```
location / {
    try_files $uri $uri/ /index.php?$args;
}
```

最后在项目配置文件 `app.php` 中，修改 router.pretty_url 为 `true`。

```php
// app.php
return [
    ...
    //路由配置
    'router' => [
        'pretty_url' => true,
        'default_route' => 'home/index', //默认路由
    ],
    ...
]
```

default_route 表示直接输入项目地址访问时的默认路由地址。例如访问 http://yourdomain/ 时，对应的控制器方法是 `App\Controller\HomeController::indexAction()`。

### 自定义路由

配置文件 `route.php` 用来配置自定义的路由规则，你可以指定的URL规则映射到不同的控制器方法上。

```php
return [
    ['/welcome', 'home/index'],
    ['/login', 'home/login', 'POST'],
    '/v1' => [
        ['/users', 'user/list'],
        ['/info/:id', 'user/info'],
    ],
];
```

上面配置将 http://yourdomain/welcome 映射到 app\Controller\HomeController::indexAction() 方法。路由配置项的格式为

```php
['URL规则', '路由地址', 'HTTP方法']
```

URL规则：可以使用变量或者通配符，变量语法是`:var`，如: /user/:id，使用通配符如：/home/*。在控制器中可以使用 `$this->request->getAttribute('id')` 获取到URL规则中的变量值，使用通配符的可以用 `$this->request->getAttribute(0)` 获取，`0` 表示第一个值。

路由地址：根据控制器的类名转换而来，格式为: 类名/方法名，省略 Controller 和 Action，例如： Admin/User/UserList 表示 App\Controller\Admin\UserController::UserListAction()，你也可以使用全小写的方式 admin/user/user-list，不管用哪种，最终都统一转换为全小写的地址。

HTTP方法：用来限制特定HTTP请求方式，只支持一个，如: GET、POST。

当多个路由地址使用同样的前缀时，可以使用组路由，例如上面例子中的：

```
    '/v1' => [
        ['/users', 'user/list'],
        ['/info/:id', 'user/info'],
    ],
```

对应的访问地址为 http://yourdomian/v1/users 和 http://yourdomian/v1/info/123 。