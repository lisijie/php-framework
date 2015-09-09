<?php

namespace Core\Mutex;

use Core\Lib\FileHelper;

/**
 * 文件锁
 *
 * 适用于单主机的应用。为了避免频繁的IO操作，锁文件创建后不会自动删除。默认所有锁文件都存放在统一目录下，如果锁太多的话（例如对每个用户ID设置不同的锁），
 * 会造成较多的垃圾文件，可能会占满磁盘inode和影响目录读写性能。
 *
 * @package Core\Mutex
 */
class FileMutex extends MutexAbstract
{
	private $path;
    private $files = array();

	protected function init()
	{
		$this->path = DATA_PATH . 'mutex/';
        if (!is_dir($this->path)) {
            FileHelper::makeDir($this->path);
        }
	}

	protected function doLock($name, $timeout)
	{
        $lockFile = $this->getLockFile($name);
        $fp = fopen($lockFile, 'w+');
        $waitTime = 0;
        while (!flock($fp, LOCK_EX|LOCK_NB)) {
            if (++$waitTime > $timeout) {
                fclose($fp);
                return false;
            }
            sleep(1);
        }
        $this->files[$name] = $fp;
        return true;
	}

	protected function doUnlock($name)
	{
        if (!isset($this->files[$name]) || !flock($this->files[$name], LOCK_UN)) {
            return false;
        }
        fclose($this->files[$name]);
        unset($this->files[$name]);
        return true;
	}

    private function getLockFile($name)
    {
        return $this->path . md5($name) . '.lock';
    }
}
