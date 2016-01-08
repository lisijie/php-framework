<?php
/**
 * URL Rewrite
 *
 * Apache 服务器在网站根目录创建 .htaccess文件，内容为：
 *    <IfModule mod_rewrite.c>
 *        RewriteEngine on
 *        RewriteBase /
 *        RewriteCond %{REQUEST_FILENAME} !-f
 *        RewriteCond %{REQUEST_FILENAME} !-d
 *        RewriteRule ^.*$ index.php?$0 [L]
 *    </IfModule>
 *
 * Nginx 服务器在 server {} 内加入：
 *  location / {
 *      try_files $uri $uri/ /index.php?$args;
 *  }
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Router
 */

namespace Core\Router;

class Rewrite extends Router
{

    public function parse()
    {
        $requestUri = $this->request->getRequestUri();
	    $parts      = parse_url($requestUri);
	    $path       = $parts['path'];
	    $query      = isset($parts['query']) ? $parts['query'] : '';
	    // 去掉项目目录
	    $baseUrl = $this->request->getBaseUrl();
        if ($baseUrl && ($pos = strpos($path, $baseUrl)) === 0) {
	        $path = substr($path, strlen($baseUrl));
        }
        $path = trim($path, '/');
        if (!empty($query)) {
            parse_str($query, $vars);
            $_GET = array_merge($_GET, (array)$vars);
        }
        $this->parseUrl($path);
    }

    public function makeUrl($route, $params = array())
    {
        $result = $this->makeUrlPath($route, $params);
        return $this->request->getBaseUrl() . '/' . ltrim($result['path'], '/') . (empty($result['params']) ? '' : '?' . http_build_query($result['params']));
    }

}
