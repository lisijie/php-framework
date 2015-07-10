<?php

namespace Core\Mutex;

use Core\Lib\FileHelper;

class FileMutex extends Mutex
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
