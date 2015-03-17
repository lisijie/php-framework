<?php

namespace Core\Router;

use Core\Http\Request;

/**
 * 路由解析器
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Router
 */
abstract class Router
{
	protected $config = array();
	//保存每个URL规则对应的路由信息
	protected $routeMap = array();
	//保存每个路由地址对应的URL信息
	protected $actionMap = array();
	//变量对应正则
	protected $vars = array(
		':id' => '(\d+)',
		':int' => '(\d+)',
		':string' => '([^/\#]*)?',
	);
	//当前路由参数
	protected $params = array();
	//当前路由地址
	protected $routeName;
	//默认路由
    protected $defaultRoute = '';
	/**
	 * 请求对象
	 *
	 * @var \Core\Http\Request
	 */
	protected $request;
	//路由变量
	protected $routeVar = 'r';

    public function __construct($options = array())
    {
        if (isset($options['default_route'])) {
            $this->defaultRoute = (string)$options['default_route'];
        }
	    if (isset($options['route_var'])) {
		    $this->routeVar = (string)$options['route_var'];
	    }
    }
	
	/**
	 * 设置路由配置
	 * @param array $config
	 */
	public function setConfig(array $config)
	{
		$this->config = $config;
		$this->parseConfig();
	}

	/**
	 * 设置请求对象
	 *
	 * @param Request $request
	 */
	public function setRequest(Request $request)
	{
		$this->request = $request;
	}

    /**
     * 设置默认路由
     * @param $route
     */
    public function setDefaultRoute($route)
    {
        $this->defaultRoute = $route;
    }

    /**
     * 返回默认路由
     * @return string
     */
    public function getDefaultRoute()
    {
        return $this->defaultRoute;
    }
	
	/**
	 * 解析配置
	 */
	protected function parseConfig()
	{
		foreach ($this->config as $conf) {
			list($url, $route, $params) = $conf;
            $route = strtolower($route);
			$re = strtr($url, $this->vars); //变量替换成正则
			$this->routeMap[$re] = array('route'=>$route, 'params'=>$params);
			$this->actionMap[$route][] = array('url'=>$url, 'params'=>$params);
		}
	}

    /**
     * 解析URL
     *
     * @param string $uri
     * @return bool
     */
    protected function parseUrl($uri)
	{
        if (empty($uri)) return false;
		$match = false;
		foreach ($this->routeMap as $re => $value) {
			if (preg_match('#^'.$re.'$#i', $uri, $matches)) {
				foreach ($value['params'] as $k => $v) {
					if ($v[0] == '$' && isset($matches[substr($v, 1)])) {
						$value['params'][$k] = $matches[substr($v, 1)];
					}
				}
				$this->routeName = $value['route'];
				$this->params = $value['params'];
				$match = true;
				break;
			}
		}
		if (!$match) {
			$this->routeName = trim($uri, '/');
		}
	}
	
	/**
	 * 根据规则生成URL路径部分
	 * 
	 * @param string $route
	 * @param array $params
     * @return array('path'=>路径, 'params'=>参数)
	 */
	protected function makeUrlPath($route, $params)
	{
        $route = strtolower($route);
		$path = '';
		if (isset($this->actionMap[$route])) {
			$map = array();
			$n = -1;
			foreach ($this->actionMap[$route] as $value) {
				//参数完全匹配
				if (count($value['params']) == count($params) && !array_diff_key($value['params'], $params)) {
					$map = &$value;
					break;
				}
				//寻找最佳匹配
				if (count(array_intersect_key($value['params'], $params)) > $n) {
					$map = &$value;
				}
			}

			//进行参数替换
			$vars = array();
			foreach ($map['params'] as $k => $v) {
				if (isset($params[$k])) {
					$vars[$v] = $params[$k];
					unset($params[$k]);
				}
			}
			$count = 1;
			$path = preg_replace_callback('#(:[a-z0-9]+)#i', function() use(&$count, &$vars) {
				return isset($vars["\${$count}"]) ? $vars['$'.$count++] : '';
			}, $map['url']);
		}
		if (!$path) $path = $route;

		return array('path'=>$path, 'params'=>$params);
	}

    /**
     * 获取路由地址
     */
    public function getRoute()
    {
        return $this->routeName ?: $this->getDefaultRoute();
    }


	public function getParams()
	{
		return $this->params;
	}

	/**
	 * 生成URL
	 *
	 * @param string $route 路由地址
	 * @param array $params 参数
	 * @return string
	 */
	abstract public function makeUrl($route, $params = array());
	
	/**
	 * 开始路由解析
	 */
	abstract public function parse();

    /**
     * 工厂方法
     * 实例化指定类型路由器
     *
     * @param array $options
     * @return \Core\Router\Router
     * @throws \InvalidArgumentException
     */
    public static function factory(array $options)
	{
		$className = '\\Core\\Router\\' . ucfirst($options['type']);
		if (class_exists($className) && is_subclass_of($className, '\\Core\\Router\Router')) {
			return new $className($options);
		}
		throw new \InvalidArgumentException("Unknown Router : {$options['type']}");
	}
}
