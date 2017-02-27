<?php
namespace Core\Logger\Formatter;

use Core\Lib\VarDumper;

/**
 * 日志格式器抽象类
 *
 * @package Core\Logger\Formatter
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
     * 解析和替换消息中的变量
     * @param array $record
     * @return string
     */
    protected function parseContext(array &$record)
    {
        if (false !== strpos($record['message'], '{')) {
            $replacements = [];
            foreach ($record['context'] as $key => $val) {
                if (is_null($val) || is_scalar($val) || (is_object($val) && method_exists($val, "__toString"))) {
                    $replacements['{' . $key . '}'] = $val;
                } elseif (is_object($val)) {
                    $replacements['{' . $key . '}'] = '[object ' . get_class($val) . ']';
                } elseif (is_array($val)) {
                    $replacements['{' . $key . '}'] = VarDumper::dumpAsString($val);
                } else {
                    $replacements['{' . $key . '}'] = '[' . gettype($val) . ']';
                }
            }
            $record['message'] = strtr($record['message'], $replacements);
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