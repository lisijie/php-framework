<?php
namespace Core\Logger\Formatter;

/**
 * 日志格式器接口
 * Class FormatterInterface
 *
 * @package Core\Logger
 * @author lisijie <lsj86@qq.com>
 */
interface FormatterInterface
{
    public function format(array $record);
}