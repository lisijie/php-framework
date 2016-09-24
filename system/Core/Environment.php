<?php
namespace Core;

use Core\Exception\CoreException;

/**
 * 运行环境
 * 
 * @author lisijie <lsj86@qq.com>
 * @package Core
 */
class Environment
{
	// 开发环境
	const DEVELOPMENT = 'development';

	// 测试环境
	const TESTING = 'testing';

	// 预发布环境
	const PRE_RELEASE = 'pre_release';

	// 生产环境
	const PRODUCTION = 'production';

	// 环境变量名称
	private static $envVar = 'ENVIRONMENT';

	// 当前环境
	private static $environment;

	// 环境文件名
	private static $envFile = '';

	/**
	 * 设置环境配置文件
	 * @param $filename
	 */
	public static function setEnvFile($filename)
	{
		self::$envFile = $filename;
	}

	/**
	 * 设置服务器环境变量名
	 * @param $varName
	 */
	public static function setEnvVar($varName)
	{
		self::$envVar = $varName;
	}

	/**
	 * 检查当前是否是某个环境
	 * @param $env
	 * @return bool
	 */
	public static function isEnvironment($env)
	{
		return self::getEnvironment() === $env;
	}

	/**
	 * 检查是否开发环境
	 * @return bool
	 */
	public static function isDevelopment()
	{
		return self::getEnvironment() == self::DEVELOPMENT;
	}

	/**
	 * 检查是否测试环境
	 * @return bool
	 */
	public static function isTesting()
	{
		return self::getEnvironment() == self::TESTING;
	}

	/**
	 * 检查是否预发布环境
	 * @return bool
	 */
	public static function isPreRelease()
	{
		return self::getEnvironment() == self::PRE_RELEASE;
	}

	/**
	 * 检查是否生产环境
	 * @return bool
	 */
	public static function isProduction()
	{
		return self::getEnvironment() == self::PRODUCTION;
	}

	/**
	 * 设置当前环境
	 * @param $env
	 * @throws CoreException
	 */
	public static function setEnvironment($env)
	{
		if (self::isValid($env)) {
			self::$environment = $env;
		}
		throw new CoreException('invalid env: ' . $env);
	}

	/**
	 * 返回当前的环境名称
	 * @return string
	 */
	public static function getEnvironment()
	{
		if (!self::$environment) {
			// 指定环境变量文件
			if (!empty(self::$envFile) && is_file(self::$envFile)
				&& self::isValid($env = file_get_contents(self::$envFile))) {
				self::$environment = $env;
			}
			// 检查$_SERVER环境变量
			if (!self::$environment && isset($_SERVER[self::$envVar])
				&& self::isValid($_SERVER[self::$envVar])) {
				self::$environment = $_SERVER[self::$envVar];
			} else {
				self::$environment = self::getDefaultEnvironment();
			}
		}
		return self::$environment;
	}

	/**
	 * 返回默认环境名称
	 * @return string
	 */
	private static function getDefaultEnvironment()
	{
		return self::PRODUCTION;
	}

	/**
	 * 验证是否有效的环境名称
	 * @param $env
	 * @return bool
	 */
	private static function isValid($env)
	{
		$ref = new \ReflectionClass(get_called_class());
		return in_array($env, array_values($ref->getConstants()));
	}
}