<?php

/**
 * 类自动加载器
 *
 * 功能：
 *   1. 根据命名空间加载
 *   2. 自定义类路径加载
 *   3. 自定义目录加载
 *
 * @author lisijie <lsj86@qq.com>
 */
class ClassLoader
{

    protected $namespaces = array();
    protected $classes = array();
    protected $paths = array();
    private static $instance;

    /**
     * 获取类的单一实例
     *
     * @return static
     */
    public static function getInstance()
    {
        if (!is_object(self::$instance)) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * 注册命名空间
     *
     * @param string $namespace 命名空间
     * @param array|string $paths 目录列表
     * @return ClassLoader
     */
    public function registerNamespace($namespace, $paths)
    {
        $this->namespaces[$namespace] = (array)$paths;
        return $this;
    }

    /**
     * 获取已注册的命名空间对应目录
     *
     * @param string $namespace
     * @return array
     */
    public function getNamespacePaths($namespace)
    {
        if (isset($this->namespaces[$namespace])) {
            return $this->namespaces[$namespace];
        }
        return array();
    }

    /**
     * 注册单个类
     *
     * @param string $className 类名
     * @param string $path 类文件的完整路径
     * @return ClassLoader
     */
    public function registerClass($className, $path)
    {
        $this->classes[$className] = (string)$path;
        return $this;
    }

    /**
     * 注册目录
     *
     * 用于对某个前缀的类指定搜索目录。
     *
     * @param string $prefix 类前缀
     * @param array|string $paths 目录列表
     * @return ClassLoader
     */
    public function registerPath($prefix, $paths)
    {
        if (!isset($this->paths[$prefix])) {
            $this->paths[$prefix] = (array)$paths;
        } else {
            $this->paths[$prefix] = array_merge($this->paths[$prefix], (array)$paths);
        }
        return $this;
    }

    /**
     * 注册自动加载
     *
     * @param bool $prepend 是否优先
     */
    public function register($prepend = false)
    {
        spl_autoload_register(array($this, 'loadClass'), true, $prepend);
    }

    public function unregister()
    {
        spl_autoload_unregister(array($this, 'loadClass'));
    }

    /**
     * 加载类
     *
     * @param $className
     * @return bool
     */
    public function loadClass($className)
    {
        $className = preg_replace('/([^a-z0-9\_\\\\])/i', '', $className);
        if ($file = $this->findFile($className)) {
            include $file;
            return true;
        }
    }

    /**
     * 类文件查找
     *
     * @param $class
     * @return bool|string
     */
    protected function findFile($class)
    {
        if ($class[0] == '\\') {
            $class = substr($class, 1);
        }
        if (isset($this->classes[$class]) && is_file($this->classes[$class])) {
            return $this->classes[$class];
        }

        if (false !== strrpos($class, '\\')) {
            foreach ($this->namespaces as $ns => $paths) {
                if (0 === strpos($class, $ns . '\\')) {
                    foreach ($paths as $path) {
                        $file = $path . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($ns . '\\'))) . '.php';
                        if (is_file($file)) {
                            return $file;
                        }
                    }
                }
            }
        }

        foreach ($this->paths as $prefix => $paths) {
            if ($prefix != '' && 0 !== strpos($class, $prefix)) {
                continue;
            }
            foreach ($paths as $path) {
                $file = $path . DIRECTORY_SEPARATOR . str_replace(array('\\', '_'), DIRECTORY_SEPARATOR, $class) . '.php';
                if (is_file($file)) {
                    return $file;
                }
            }
        }

        return false;
    }
}
