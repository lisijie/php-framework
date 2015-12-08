<?php
namespace Core\Lib;

/**
 * 文件操作助手类
 *
 * @package Core\Lib
 */
class FileHelper
{

    static $mimeTypes = array();

    /**
     * 创建目录
     *
     * @param string $path 路径
     * @param int $mode 模式
     * @param bool $recursive 是否递归创建
     * @return bool 成功返回true,目录已存在或不合法返回false
     */
    public static function makeDir($path, $mode = 0755, $recursive = true)
    {
        if (is_dir($path)) {
            return true;
        }
        if (!is_dir(dirname($path)) && $recursive) {
            static::makeDir(dirname($path), $mode, true);
        }
        $result = mkdir($path, $mode);
        chmod($path, $mode);

        return $result;
    }

    /**
     * 删除目录及其目录下的所有文件和子目录
     *
     * @param string $path 要删除的目录
     * @return void
     */
    public static function removeDir($path, $recursive = true)
    {
        $path = rtrim($path, '/');
        if (($handle = opendir($path)) !== false) {
            while (false !== ($d = readdir($handle))) {
                if ($d != '.' && $d != '..') {
                    if (is_dir($path . '/' . $d)) {
                        static::removeDir($path . '/' . $d);
                        @rmdir($path . '/' . $d);
                    } else {
                        @unlink($path . '/' . $d);
                    }
                }
            }
            closedir($handle);
        }
    }

    /**
     * 数据大小单位转换
     */
    public static function sizeFormat($byte)
    {
        $s = array('Byte', 'KB', 'MB', 'GB', 'TB');
        $i = 0;
        while ($byte > 1024) {
            $byte /= 1024;
            $i++;
        }
        return round($byte, 2) . $s[$i];
    }

    /**
     * 检查文件或目录是否可写
     *
     * @param string $filename 文件或目录
     * @return bool
     */
    public static function writeable($filename)
    {
        return is_writable($filename);
    }

    /**
     * 检查文件或目录是否可读
     *
     * @param string $filename 文件或目录
     * @return bool
     */
    public static function readable($filename)
    {
        return is_readable($filename);
    }

    /**
     * 检查是否有效文件名
     *
     * @param string $string
     * @return bool
     */
    public static function checkName($string)
    {
        return preg_match('/^[a-z0-9\_\]+$/', $string);
    }

    /**
     * 列出该目录及其子目录下的所有文件
     *
     * @param string $dir 目录名
     * @return array
     */
    public static function scanDir($dir)
    {
        $dir = rtrim($dir, '/\\');
        $result = array();
        if (is_dir($dir)) {
            if ($d = opendir($dir)) {
                while (false !== ($file = readdir($d))) {
                    if ($file != '.' && $file != '..') {
                        if (is_dir($dir . DIRECTORY_SEPARATOR . $file)) {
                            $result = array_merge($result, static::scanDir($dir . DIRECTORY_SEPARATOR . $file));
                        } else {
                            $result[] = $dir . DIRECTORY_SEPARATOR . $file;
                        }
                    }
                }
                closedir($d);
            }
        }
        return $result;
    }

    /**
     * 返回文件小写扩展名
     *
     * @param string $filename
     * @return string
     */
    public static function getFileExt($filename)
    {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }

    /**
     * 获取文件的MimeType
     *
     * @param string $filename 文件名
     * @return null|string
     */
    public static function getMimeType($filename)
    {
        if (($ext = static::getFileExt($filename)) != "") {
            return static::getMimeTypeByExt($ext);
        }
        return null;
    }

    /**
     * 根据扩展名获取对应MimeType
     *
     * @param string $ext 扩展名
     * @return string
     */
    public static function getMimeTypeByExt($ext)
    {
        self::loadMimeTypes();
        return isset(static::$mimeTypes[$ext]) ? static::$mimeTypes[$ext] : '';
    }

    /**
     * 加载mimeType配置信息
     */
    private static function loadMimeTypes()
    {
        if (empty(self::$mimeTypes)) {
            self::$mimeTypes = require __DIR__ . '/mimeTypes.php';
        }
    }

	/**
	 * 递归拷贝
	 *
	 * @param string $src 源路径
	 * @param string $dst 目标路径
	 * @return bool
	 */
	public static function copyRecurse($src, $dst) {
		$dir = opendir($src);
		if (!$dir) return false;
		is_dir($dst) || mkdir($dst);
		while(false !== ($file = readdir($dir))) {
			if (($file != '.') && ($file != '..')) {
				if (is_dir($src . '/' . $file)) {
					self::copyRecurse($src . '/' . $file, $dst . '/' . $file);
				} else {
					copy($src . '/' . $file, $dst . '/' . $file);
				}
			}
		}
		closedir($dir);
		return true;
	}
}
