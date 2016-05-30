<?php
namespace Core;
/**
 * 配置信息读写类
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core
 */
class Config extends Object
{
	private $data = [];
	private $configPath = '';
	private $env = '';

	/**
	 * 构造函数
	 *
	 * @param string $configPath 配置文件目录
	 * @param string $env 运行环境
	 */
	public function __construct($configPath, $env)
	{
		if (is_string($configPath)) {
			$this->configPath = $configPath;
		}
		if (is_string($env)) {
			$this->env = $env;
		}
	}

	/**
	 * 加载配置文件
	 *
	 * 配置文件位于 $configPath 定义的目录下，支持主配置和各个环境的差异配置。
	 * 主配置位于 $configPath 根目录，差异配置位于 $env 子目录。
	 * 如果同时存在主配置和差异配置，将先加载主配置，然后加载差异配置覆盖主配置中相同的键。
	 *
	 * @param $file
	 */
	private function load($file)
	{
		$mainFile = $this->configPath . $file . '.php';
		$diffFile = $this->configPath . $this->env . '/' . $file . '.php';
		if (!is_file($mainFile) && !is_file($diffFile)) {
			die("配置文件不存在: {$file}");
		}
		$config = [];
		if (is_file($mainFile)) {
			$config = include $mainFile;
		}
		if (is_file($diffFile)) {
			$diff = include $diffFile;
			if (is_array($diff) && !empty($diff)) {
				$config = array($config, $diff);
			}
		}
		$this->data[$file] = $config;
	}

	/**
	 * 获取配置信息
	 *
	 * @param string $file 配置文件名，不带扩展名
	 * @param string $name 配置键名
	 * @param null $default 默认值
	 * @return mixed|null
	 */
	public function get($file, $name = '', $default = null)
	{
		if (!preg_match('/^[a-z0-9\_]+$/i', $file)) return null;
		if (!isset($this->data[$file])) {
			$this->load($file);
		}
		if (empty($name)) {
			return $this->data[$file];
		} else {
			return isset($this->data[$file][$name]) ? $this->data[$file][$name] : $default;
		}
	}

	/**
	 * 修改配置信息
	 *
	 * 用于在运行时动态修改配置信息，如：
	 * 根据用户的系统语言动态加载不同的语言包
	 *
	 * @param string $file 配置文件名，不带扩展名
	 * @param string $name 配置键名
	 * @param mixed $value 值
	 */
	public function set($file, $name, $value)
	{
		if (!isset($this->data[$file])) {
			$this->load($file);
		}
		$this->data[$file][$name] = $value;
	}
}