<?php
namespace Core\Console;

use App;
use ClassLoader;
use Core\Command;
use Core\Lib\Console;
use Core\Lib\FileHelper;

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
        $paths = $this->getCommandPaths();
        $commands = [];
        $len = 0;
        foreach ($controllers as $controller) {
            $actions = $this->getControllerActions($controller);
            if (count($actions) > 0) {
                foreach ($actions as $name => $action) {
                    $command = str_replace(array_keys($paths), '', substr($controller, 0, -7));
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
            $string = str_replace(array_keys($this->getCommandPaths()), '', substr($controller, 0, -7));
            $string = ltrim(strtr($string, '\\', '/'), '/');
            fprintf(STDOUT, Console::ansiFormat("- {$string}", Console::FG_YELLOW));
            fprintf(STDOUT, str_repeat(' ', $len - strlen($string) + 10));
            fprintf(STDOUT, Console::ansiFormat($this->parseCommentSummary($class->getDocComment()), Console::BOLD));
            fprintf(STDOUT, "\n");
            foreach ($actions as $name => $summary) {
                fprintf(STDOUT, '    ' . Console::ansiFormat($name, Console::FG_GREEN));
                fprintf(STDOUT, str_repeat(' ', $len - strlen($name) + 8));
                fprintf(STDOUT, $summary);
                fprintf(STDOUT, "\n");
            }
        }
    }

    /**
     * 获取控制器列表
     */
    private function getControllers()
    {
        $controllers = [];
        $paths = $this->getCommandPaths();
        foreach ($paths as $ns => $nsPaths) {
            foreach ($nsPaths as $pathInfo) {
                list($path, $suffix) = $pathInfo;
                $files = FileHelper::scanDir($path);
                $fileSuffix = $suffix . '.php';
                foreach ($files as $file) {
                    if (substr_compare($file, $fileSuffix, 0 - strlen($fileSuffix), strlen($fileSuffix)) !== 0) {
                        continue;
                    }
                    $file = substr($file, strlen($path), -4);
                    $controllers[] = $ns . strtr($file, '/', '\\');
                }
            }
        }
        return $controllers;
    }

    private function getCommandPaths()
    {
        $namespaces = App::router()->getNamespaces();
        $loader = ClassLoader::getInstance();
        $paths = [];
        foreach ($namespaces as $namespace) {
            list($prefix, $suffix) = $namespace;
            $ns = '';
            foreach (explode('\\', $prefix) as $p) {
                $ns .= $p;
                $values = $loader->getNamespacePaths($ns);
                if ($values) {
                    foreach ($values as $value) {
                        $path = $value . substr($prefix, strlen($ns));
                        $path = str_replace('\\', '/', $path);
                        $paths[$prefix][] = [$path, $suffix];
                    }
                    continue 2;
                }
                $ns .= '\\';
            }
        }
        return $paths;
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
