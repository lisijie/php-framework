<?php

namespace Core\View;

require SYS_PATH . 'Vendor/Smarty/Smarty.class.php';

/**
 * Smarty模版引擎
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\View
 */
class Smarty extends ViewAbstract
{
	private $smarty;

	public function __construct(array $options)
	{
        if (!isset($options['ext'])) {
            $options['ext'] = '.html';
        }
        $this->options = $options;
        $defaults = array(
            'template_dir' => VIEW_PATH,
            'config_dir'   => VIEW_PATH . 'config' . DIRECTORY_SEPARATOR,
            'compile_dir'  => DATA_PATH . 'cache/smarty_complied',
            'cache_dir'    => DATA_PATH . 'cache/smarty_cache',
        );
		$this->smarty = new \Smarty();
		foreach ($defaults as $key => $value) {
            $this->smarty->{$key} = isset($options[$key]) ? $options[$key] : $value;
		}
	}

    public function render($filename, $data = array())
    {
        $filename = $filename . $this->getOption('ext');
	    if (!empty($data)) {
		    $this->assign($data);
	    }
	    $this->smarty->assign($this->data);
		return $this->smarty->fetch($filename);
    }

    public function __call($method, $args)
    {
        return call_user_func_array(array($this->smarty, $method), $args);
    }
}
