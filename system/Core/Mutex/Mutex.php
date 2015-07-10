<?php

namespace Core\Mutex;

abstract class Mutex
{
	private $locks = array();
	private $autoUnlock = true;

	public final function __construct($autoUnlock = true)
	{
		$this->autoUnlock = $autoUnlock;
		if ($this->autoUnlock) {
			$locks = &$this->locks;
			register_shutdown_function(function () use(&$locks) {
				foreach ($locks as $lock => $count) {
					$this->unlock($lock);
				}
			});
		}
        $this->init();
	}

    protected function init()
    {

    }

	public function lock($name, $timeout = 0)
	{
		if ($this->doLock($name, $timeout)) {
			$this->locks[$name] = true;
			return true;
		}
		return false;
	}

	public function unlock($name)
	{
		if ($this->doUnlock($name)) {
			unset($this->locks[$name]);
			return true;
		}
		return false;
	}

	abstract protected function doLock($name, $timeout);

	abstract protected function doUnlock($name);

}