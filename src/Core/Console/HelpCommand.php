<?php
namespace Core\Console;

use App;
use ClassLoader;
use Core\Command;
use Core\Exception\AppException;
use Core\Lib\Console;
use Core\Lib\Files;

/**
 * 显示帮助
 *
 * @package Core\Console
 */
class HelpCommand extends Command
{

    private $command;

    /**
     * 显示帮助信息
     *
     * @param string $command 命令
     */
    public function indexAction($command = null)
    {
        $this->command = $command;
        if ($command === null) {
            $this->showCommandList();
        } else {
            $router = App::router();
            list($controller, $action) = $router->resolve(['', $command]);
            try {
                if (empty($action)) {
                    $this->showCommandHelp($controller);
                } else {
                    $this->showCommandDetail($controller, $action);
                }
            } catch (AppException $e) {
                $this->message("命令 '{$command}' 不存在。", MSG_ERR);
            }
        }
    }

    /**
     * 显示命令详情
     *
     * @param string $className
     * @param string $actionName
     * @throws AppException
     */
    private function showCommandDetail($className, $actionName)
    {
        $actions = $this->getControllerActions($className);
        $methodRef = null;
        foreach ($actions as $name => $action) {
            if (strtolower($name) == strtolower($actionName)) {
                $methodRef = $action;
                break;
            }
        }
        if (!$methodRef) {
            throw new AppException('命令不存在。');
        }
        assert($methodRef instanceof \ReflectionMethod);

        echo Console::ansiFormat("命令说明：\n\n", Console::BOLD);

        echo $this->parseCommentDescription($methodRef->getDocComment()) . "\n\n";

        echo Console::ansiFormat("用法：\n\n", Console::BOLD);

        $params = $this->parseMethodParams($methodRef);
        $usage = "{$_SERVER['argv'][0]} " . Console::ansiFormat($this->command, Console::FG_YELLOW);
        $paramsDesc = '';
        foreach ($params as $param) {
            if ($param['required']) {
                $usage .= ' ' . Console::ansiFormat("<{$param['name']}>", Console::FG_CYAN);
                $paramsDesc .= '- ' . Console::ansiFormat($param['name'], Console::FG_CYAN) . ' (必须)';
                if (!empty($param['type'])) {
                    $paramsDesc .= ': ' . $param['type'];
                }
            } else {
                $usage .= ' ' . Console::ansiFormat("[{$param['name']}]", Console::FG_CYAN);
                $paramsDesc .= '- ' . Console::ansiFormat($param['name'], Console::FG_CYAN) . ": {$param['type']}";
                $paramsDesc .= " (默认值: {$param['default']})";
            }
            if (!empty($param['desc'])) {
                $paramsDesc .= "\n  {$param['desc']}";
            }
            $paramsDesc .= "\n";
        }

        echo $usage . "\n\n";
        echo $paramsDesc . "\n\n";
    }

    private function parseMethodParams(\ReflectionMethod $method)
    {
        $paramsComments = [];

        $comments = preg_split('|\R|u', $method->getDocComment());
        array_shift($comments);
        foreach ($comments as $line) {
            $line = ltrim($line, "\t *");
            if (strpos($line, '@param') === 0) {
                $parts = preg_split('/\s+/', $line, 4);
                if (count($parts) < 3) {
                    continue;
                }
                if ($parts[1][0] == '$') { // 没有类型说明
                    $paramsComments[substr($parts[1], 1)] = ['type' => '', 'desc' => implode(' ', array_slice($parts, 2))];
                } else {
                    $paramsComments[substr($parts[2], 1)] = ['type' => $parts[1], 'desc' => isset($parts[3]) ? $parts[3] : ''];
                }
            }
        }

        $params = [];
        foreach ($method->getParameters() as $parameter) {
            $info = [
                'name' => $parameter->getName(),
                'required' => !$parameter->isOptional(),
                'default' => '',
                'type' => '',
                'desc' => '',
            ];
            if (!$info['required']) {
                $info['default'] = $parameter->getDefaultValue();
            }
            if (isset($paramsComments[$parameter->getName()])) {
                $info['type'] = $paramsComments[$parameter->getName()]['type'];
                $info['desc'] = $paramsComments[$parameter->getName()]['desc'];
            }
            $params[$parameter->getName()] = $info;
        }

        return $params;
    }

    /**
     * 显示命令的帮助信息
     *
     * @param $className
     * @throws AppException
     */
    private function showCommandHelp($className)
    {
        if (!class_exists($className)) {
            throw new AppException('命令不存在。');
        }
        $router = App::router();
        $paths = $this->getCommandPaths();

        // 提取前缀和后缀列表
        $prefixArr = $suffixArr = [];
        foreach ($paths as $prefix => $values) {
            $prefixArr[] = $prefix;
            foreach ($values as $value) {
                $suffixArr[] = $value[1];
            }
        }

        $class = $this->getReflectionClass($className);
        echo Console::ansiFormat("命令说明：\n\n", Console::BOLD);

        echo $this->parseCommentDescription($class->getDocComment()) . "\n\n";

        $actions = $this->getControllerActions($className);
        if (!empty($actions)) {
            echo Console::ansiFormat("子命令列表：\n\n", Console::BOLD);
            $maxLen = 0;
            $commands = [];
            foreach ($actions as $name => $action) {
                $command = str_replace($prefixArr, '', $className);
                foreach ($suffixArr as $suffix) {
                    if (substr($command, 0 - strlen($suffix)) == $suffix) {
                        $command = substr($command, 0, 0 - strlen($suffix));
                        break;
                    }
                }
                $command = ltrim(strtr($command, '\\', '/'), '/') . '/' . $name;
                $command = $router->normalizeRoute($command);
                $comment = $this->parseCommentSummary($action->getDocComment());
                if (strlen($command) > $maxLen) {
                    $maxLen = strlen($command);
                }
                $commands[$command] = trim($comment);
            }
            foreach ($commands as $command => $comment) {
                echo '- ' . Console::ansiFormat("{$command}", Console::FG_YELLOW);
                if (!empty($comment)) {
                    echo str_repeat(' ', $maxLen - strlen($command) + 2) . $comment . "\n";
                }
            }
            echo Console::ansiFormat("\n使用 help 命令可查看子命令的详细帮助信息：", Console::BOLD) . "\n\n";
            echo "  {$_SERVER['argv'][0]} " . Console::ansiFormat("help", Console::FG_YELLOW) . ' ' . Console::ansiFormat('<sub-command>', Console::FG_CYAN) . "\n\n";
        }
    }

    /**
     * 显示所有命令列表
     */
    private function showCommandList()
    {
        $controllers = $this->getControllers();
        $paths = $this->getCommandPaths();
        $router = App::router();
        // 提取前缀和后缀列表
        $prefixArr = $suffixArr = [];
        foreach ($paths as $prefix => $values) {
            $prefixArr[] = $prefix;
            foreach ($values as $value) {
                $suffixArr[] = $value[1];
            }
        }
        $commands = [];
        $maxLen = 0;
        // 提取每个控制器的命令列表
        foreach ($controllers as $controller) {
            $actions = $this->getControllerActions($controller);
            if (count($actions) > 0) {
                foreach ($actions as $name => $action) {
                    $command = str_replace($prefixArr, '', $controller);
                    foreach ($suffixArr as $suffix) {
                        if (substr($command, 0 - strlen($suffix)) == $suffix) {
                            $command = substr($command, 0, 0 - strlen($suffix));
                            break;
                        }
                    }
                    $command = ltrim(strtr($command, '\\', '/'), '/') . '/' . $name;
                    $command = $router->normalizeRoute($command);
                    $commands[$controller][$command] = $this->parseCommentSummary($action->getDocComment());
                    if (strlen($command) > $maxLen) {
                        $maxLen = strlen($command);
                    }
                }
            }
        }
        // 格式化输出
        fprintf(STDOUT, Console::ansiFormat("可用的命令列表：\n\n", Console::BOLD));
        foreach ($commands as $controller => $actions) {
            $class = $this->getReflectionClass($controller);
            $string = str_replace($prefixArr, '', $controller);
            foreach ($suffixArr as $suffix) {
                if (substr($string, 0 - strlen($suffix)) == $suffix) {
                    $string = substr($string, 0, 0 - strlen($suffix));
                    break;
                }
            }
            $string = ltrim(strtr($string, '\\', '/'), '/');
            $string = $router->normalizeRoute($string);
            fprintf(STDOUT, Console::ansiFormat("- {$string}", Console::FG_YELLOW));
            fprintf(STDOUT, str_repeat(' ', $maxLen - strlen($string) + 10));
            fprintf(STDOUT, Console::ansiFormat($this->parseCommentSummary($class->getDocComment()), Console::BOLD));
            fprintf(STDOUT, "\n");
            foreach ($actions as $name => $summary) {
                fprintf(STDOUT, '    ' . Console::ansiFormat($name, Console::FG_GREEN));
                fprintf(STDOUT, str_repeat(' ', $maxLen - strlen($name) + 8));
                fprintf(STDOUT, $summary);
                fprintf(STDOUT, "\n");
            }
        }
        fprintf(STDOUT, Console::ansiFormat("\n使用 help 命令可查看帮助信息：\n\n", Console::BOLD));
        fprintf(STDOUT, "  {$_SERVER['argv'][0]} " . Console::ansiFormat("help", Console::FG_YELLOW) . ' ' . Console::ansiFormat('<command>', Console::FG_CYAN) . "\n\n");
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
                $files = Files::scanDir($path);
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

    /**
     * 解析注释中的说明信息
     *
     * @param string $comment
     * @return string
     */
    private function parseCommentDescription($comment)
    {
        // \R 可以匹配 \r,\n,\r\n 三种换行符
        $comments = preg_split('|\R|u', $comment);
        array_shift($comments);
        $blank = false;
        $desc = '';
        foreach ($comments as $line) {
            $line = ltrim($line, "\t *");
            if (!empty($line) && $line[0] == '@') {
                break;
            }
            if ($blank && empty($line)) {
                continue;
            }
            $blank = empty($line);
            $desc .= $line . "\n";
        }
        $desc = trim($desc);
        return $desc;
    }
}
