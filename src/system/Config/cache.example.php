<?php
/**
 * 缓存配置示例
 *
 * @author lisijie <lsj86@qq.com>
 * @package config
 */

return array(

	/**
	 * 使用App::getCache()不指定参数时默认使用的缓存驱动
	 */
	'default' => 'file',

	/**
	 * 文件缓存
	 * 适用于使用单台web机且没条件安装其他缓存服务的应用
	 */
	'file' => array(
		//key前缀
		'prefix' => '',
		//缓存文件保存目录
		'save_path' => DATA_PATH . 'cache/',
	),

	/**
	 * APC缓存
	 *
	 * 存放在APC扩展申请的内存空间，适用场景:
	 * 1. 使用单台web的应用
	 * 2. 配合memcached或者redis做本地的二级缓存
	 */
	'apc' => array(
		//key前缀
		'prefix' => '',
	),

	/**
	 * memcached 缓存
	 *
	 * 独立的缓存服务，
	 */
	'memcached' => array(
		//key前缀
		'prefix' => '',
		//服务器列表
		'servers' => array(
			array('192.168.1.1', 11211), //memcached服务器1
			array('192.168.1.2', 11211), //memcached服务器2
		),
	),

	'redis' => array(
		//key前缀
		'prefix' => '',
        //地址
        'host' => '',
        //端口
        'port' => '',
        //超时设置
        'timeout' => 0,
	),
);