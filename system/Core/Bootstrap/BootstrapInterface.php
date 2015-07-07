<?php
namespace Core\Bootstrap;

/**
 * 引导程序接口
 *
 * @package Core\Bootstrap
 */
interface BootstrapInterface
{
	public function startup();

	public function initDb($name = 'default');

	public function initCache($name = 'default');

	public function initLogger($name = 'default');

	public function initRequest();

	public function initResponse();

	public function initSession();

	public function initRouter();

	public function initView();
}