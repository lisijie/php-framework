<?php
namespace Core\Logger\Formatter;

/**
 * JSON日志格式器
 *
 * @package Core\Logger
 * @author lisijie <lsj86@qq.com>
 */
class JsonFormatter extends AbstractFormatter
{
	public function format(array $record)
	{
		if (false !== strpos($record['message'], '{')) {
			$replacements = [];
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

		$message = [
			'datetime' => $record['datetime']->format($this->getDateFormat()),
			'channel' => strtoupper($record['channel']),
			'level' => $record['level_name'],
			'message' => $record['message'],
		];

		return json_encode($message);
	}
}