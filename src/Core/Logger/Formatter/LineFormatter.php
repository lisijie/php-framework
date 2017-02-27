<?php
namespace Core\Logger\Formatter;

/**
 * 普通行日志格式器
 *
 * @package Core\Logger\Formatter
 * @author lisijie <lsj86@qq.com>
 */
class LineFormatter extends AbstractFormatter
{
    public function format(array $record)
    {
        $this->parseContext($record);

        $message = $record['datetime']->format($this->getDateFormat()) . " [{$record['channel']}] [{$record['level_name'][0]}] [{$record['file']}:{$record['line']}] {$record['message']}";

        return $message;
    }
}