<?php
namespace Core\Logger\Formatter;

/**
 * 控制台输出的日志格式器
 *
 * @package core\logger\formatter
 * @author lisijie <lsj86@qq.com>
 */
class ConsoleFormatter extends AbstractFormatter
{
	public function format(array $record)
	{
		if (false !== strpos($record['message'], '{')) {
			$replacements = array();
			foreach ($record['context'] as $key => $val) {
				if (is_null($val) || is_scalar($val) || (is_object($val) && method_exists($val, "__toString"))) {
					$replacements['{' . $key . '}'] = $val;
				} elseif (is_object($val)) {
					$replacements['{' . $key . '}'] = '[object ' . get_class($val) . ']';
				} else {
					$replacements['{' . $key . '}'] = '[' . gettype($val) . ']';
				}
			}
			$record['message'] = strtr($record['message'], $replacements);
		}

		$message = "[" . $record['datetime']->format($this->getDateFormat()) . "] [{$record['channel']}] [" . $this->colorLevelName($record['level_name']) . "] {$record['message']}";

		return $message;
	}

	private function colorLevelName($levelName)
	{
		switch ($levelName) {
			case 'DEBUG':
				return "\033[1;34m{$levelName}\033[0m";
				break;
			case 'INFO':
				return "\033[1;34m{$levelName}\033[0m";
				break;
			case 'WARN':
				return "\033[1;33m{$levelName}\033[0m";
				break;
			case 'ERROR':
				return "\033[1;31m{$levelName}\033[0m";
				break;
			case 'FATAL':
				return "\033[1;35m{$levelName}\033[0m";
				break;
		}
		return $levelName;
	}
}