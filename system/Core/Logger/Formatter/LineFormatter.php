<?php
namespace Core\Logger\Formatter;

/**
 * 日志格式器接口
 * Class FormatterInterface
 *
 * @package Core\Logger
 * @author lisijie <lsj86@qq.com>
 */
class LineFormatter implements FormatterInterface
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

        $record['channel'] = strtoupper($record['channel']);
        $message = "[" . $record['datetime']->format('Y-m-d H:i:s') . "] [{$record['channel']}] [{$record['level_name']}] {$record['message']}\n";

        return $message;
    }
}