<?php
namespace Core\Logger\Formatter;

/**
 * 控制台输出的日志格式器
 *
 * @package Core\Logger\Formatter
 * @author lisijie <lsj86@qq.com>
 */
class ConsoleFormatter extends AbstractFormatter
{
    public function format(array $record)
    {
        $this->parseContext($record);

        $message = $record['datetime']->format($this->getDateFormat()) . " [{$record['channel']}] [" . $this->colorLevelName($record['level_name'][0]) . "] [{$record['file']}:{$record['line']}] {$record['message']}";

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