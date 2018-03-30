# 路由

## 开启 URL Rewrite
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