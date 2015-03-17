<?php

namespace Core\Lib;

class File
{
	
	/**
	 * 创建目录
	 * 
	 * 支持创建多级目录，目录权限为777，并在目录下生成名为index.html的空文件
	 * @param string $path 路径
	 * @return boolean 成功返回true,目录已存在或不合法返回false
	 */
	public static function makeDir($path)
	{
		if ( is_dir($path) || strpos($path, '..') !== false ) return false;
		$path = str_replace('\\', '/', $path);
		$dirs = explode('/', $path);
		$path = '';
		foreach ($dirs as $dir) {
			$path .= $dir;
			if ( !is_dir($path) ) {
				@mkdir($path);
				@fopen($path.'/index.html','wb');
				@chmod($path, 0777);
			}
			$path .= DS;
		}
		return true;
	}

	/**
	 * 递归删除整个目录
	 * 
	 * @param string $path 要删除的目录
	 * @return void
	 */
	public static function removeDir($path)
	{
		$path = rtrim($path, '/');
		if (($handle = opendir($path)) !== false) {
			while ( false !== ($d = readdir($handle)) ) {
				if ( $d != '.' && $d != '..' ) {
					if ( is_dir($path.'/'.$d) ) {
						self::removeDir($path.'/'.$d);
						@rmdir($path.'/'.$d);
					} else {
						@unlink($path.'/'.$d);
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
			$i ++;
		}
		return round($byte, 2).$s[$i];
	}
	
	/**
	 * 检查文件或目录是否可写
	 * @param string $filename 文件或目录
	 */
	public static function writeable($filename)
	{
		return is_writable($filename);
	}
	
	/**
	 * 检查文件或目录是否可读
	 * @param string $filename 文件或目录
	 */
	public static function readable($filename)
	{
		return is_readable($filename);
	}
	
	/**
	 * 检查是否有效文件名
	 * @param string $string
	 */
	public static function checkName($string)
	{
		return preg_match('/^[a-z0-9\_\]+$/', $string);
	}
	
	/**
	 * 返回文件小写扩展名
	 * @param string $filename
	 */
	public static function ext($filename)
	{
		return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
	}
}
