<?php
namespace Core\Logger\Handler;

use Core\Logger\Formatter\ConsoleFormatter;

/**
 * 标准输出日志处理器
 *
 * @author lisijie <lsj86@qq.com>
 * @package core\logger\handler
 */
class ConsoleHandler extends AbstractHandler
{
	private $isCli = false;

    public function __construct(array $config = [])
    {
	    $this->isCli = (php_sapi_name() == 'cli');
    }

    public function getDefaultFormatter()
    {
        return new ConsoleFormatter();
    }

    public function handle(array $record)
    {
	    if ($this->isCli) {
		    $message = $this->getFormatter()->format($record);
		    fwrite(STDOUT, $message . "\n");
	    }
    }

}
