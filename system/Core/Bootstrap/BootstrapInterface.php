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

	public function initDb($name);

	public function initCache($name);

	public function initLogger($name);

	public function initSession();

	public function initRouter();

	public function initView();
}