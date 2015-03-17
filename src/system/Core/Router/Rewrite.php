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
 *  if (!-e $request_filename) {
 *      rewrite ^(.*)$ /index.php?$1 last;
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
        $requestUri = rawurldecode($this->request->getServer('REQUEST_URI'));
        $basePath = $this->request->getBasePath();
        if ($basePath && ($pos = strpos($requestUri, $basePath)) === 0) {
            $requestUri = substr($requestUri, strlen($basePath));
        }
        if (strpos($requestUri, '?') !== false) {
            list($path, $query) = explode('?', $requestUri);
        } else {
            $path = $requestUri;
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
        return $this->request->getBasePath() . '/' . $result['path'] . (empty($result['params']) ? '' : '?' . http_build_query($result['params']));
    }

}
