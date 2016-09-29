<?php
namespace Core\Console;

use App;
use Core\Command;
use Core\Lib\FileHelper;
use Core\Lib\Console;

/**
 * 显示帮助
 *
 * @package Core\Console
 */
class HelpCommand extends Command
{

	/**
	 * 显示帮助信息
	 *
	 * @param string $command 命令
	 */
	public function indexAction($command = null)
	{
		if ($command === null) {
			return $this->showCommandList();
		}

	}

	/**
	 * 显示所有命令列表
	 */
	private function showCommandList()
	{
		$controllers = $this->getControllers();
		$commands = [];
		$len = 0;
		foreach ($controllers as $controller) {
			$actions = $this->getControllerActions($controller);
			if (count($actions) > 0) {
				foreach ($actions as $name => $action) {
					$command = str_replace(array_keys(\App::getControllerPaths()), '', substr($controller, 0, -7));
					$command = ltrim(strtr($command, '\\', '/'), '/') . '/' . $name;
					$commands[$controller][$command] = $this->parseCommentSummary($action->getDocComment());
					if (strlen($command) > $len) {
						$len = strlen($command);
					}
				}
			}
		}

		foreach ($commands as $controller => $actions) {
			$class = $this->getReflectionClass($controller);
			$string = str_replace(array_keys(\App::getControllerPaths()), '', substr($controller, 0, -7));
			$string = ltrim(strtr($string, '\\', '/'), '/');
			$this->stdout(Console::ansiFormat("- {$string}", Console::FG_YELLOW));
			$this->stdout(str_repeat(' ', $len - strlen($string) + 10));
			$this->stdout(Console::ansiFormat($this->parseCommentSummary($class->getDocComment()), Console::BOLD));
			$this->stdout("\n");
			foreach ($actions as $name => $summary) {
				$this->stdout('    ' . Console::ansiFormat($name, Console::FG_GREEN));
				$this->stdout(str_repeat(' ', $len - strlen($name) + 8));
				$this->stdout($summary);
				$this->stdout("\n");
			}
		}
	}

	/**
	 * 获取控制器列表
	 */
	private function getControllers()
	{
		$controllers = [];
		$paths = App::getControllerPaths();
		foreach ($paths as $ns => $nsPaths) {
			foreach ($nsPaths as $path) {
				$files = FileHelper::scanDir($path);
				foreach ($files as $file) {
					if (substr_compare($file, 'Command.php', -11, 11) !== 0) {
						continue;
					}
					$file = substr($file, strlen($path), -4);
					$controllers[] = $ns . strtr($file, '/', '\\');
				}
			}
		}
		return $controllers;
	}

	/**
	 * 获取控制器的动作列表
	 *
	 * @param string $controller
	 * @return array
	 */
	private function getControllerActions($controller)
	{
		$actions = [];
		$class = $this->getReflectionClass($controller);
		$methods = $class->getMethods(\ReflectionMethod::IS_PUBLIC);
		foreach ($methods as $method) {
			if (substr_compare($method->getName(), 'Action', -6, 6) === 0) {
				$actions[substr($method->getName(), 0, -6)] = $method;
			}
		}
		return $actions;
	}

	/**
	 * 获取反射类
	 *
	 * @param $class
	 * @return \ReflectionClass
	 */
	private function getReflectionClass($class)
	{
		static $objects = [];
		if (!isset($objects[$class])) {
			$objects[$class] = new \ReflectionClass($class);
		}
		return $objects[$class];
	}

	/**
	 * 获取注释的摘要部分（第一行）
	 *
	 * @param $comment
	 * @return string
	 */
	private function parseCommentSummary($comment)
	{
		// \R 可以匹配 \r,\n,\r\n 三种换行符
		$comments = preg_split('|\R|u', $comment);
		if (isset($comments[1])) {
			return ltrim($comments[1], "\t *");
		}
		return '';
	}

}
