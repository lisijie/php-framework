<?php
namespace Core\Logger\Formatter;

/**
 * 日志格式器接口
 *
 * @package Core\Logger
 * @author lisijie <lsj86@qq.com>
 */
interface FormatterInterface
{
	// 设置日期格式
	public function setDateFormat($format);

	// 获取日期格式
	public function getDateFormat();

	// 格式化日志
    public function format(array $record);
}