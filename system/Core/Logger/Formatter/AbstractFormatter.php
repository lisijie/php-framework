<?php
namespace Core\Logger\Formatter;

/**
 * 日志格式器抽象类
 *
 * @package Core\Logger
 * @author lisijie <lsj86@qq.com>
 */
abstract class AbstractFormatter implements FormatterInterface
{
    private $dateFormat = 'Y-m-d H:i:s';

    /**
     * 返回日期格式
     *
     * @return string
     */
    public function getDateFormat()
    {
        return $this->dateFormat;
    }

    /**
     * 设置日期格式
     *
     * @param string $dateFormat
     */
    public function setDateFormat($dateFormat)
    {
        if (is_string($dateFormat)) {
            $this->dateFormat = $dateFormat;
        }
    }

    /**
     * 日志格式化
     *
     * @param array $record
     * @return mixed
     */
    abstract function format(array $record);
}