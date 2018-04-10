<?php
namespace Core\Logger\Formatter;

/**
 * 数组日志格式器
 *
 * @package Core\Logger\Formatter
 * @author lisijie <lsj86@qq.com>
 */
class ArrayFormatter extends AbstractFormatter
{
    public function format(array $record)
    {
        $this->parseContext($record);

        $message = [
            'datetime' => $record['datetime']->format($this->getDateFormat()),
            'channel' => $record['channel'],
            'level' => $record['level_name'],
            'message' => $record['message'],
            'file' => $record['file'],
            'line' => $record['line'],
        ];

        return $message;
    }
}