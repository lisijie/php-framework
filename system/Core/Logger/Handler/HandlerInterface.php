<?php

namespace Core\Logger\Handler;

use Core\Logger\Formatter\FormatterInterface;

/**
 * 日志处理器接口
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Logger
 */
interface HandlerInterface
{

    /**
     * 构造函数
     *
     * @param array $config 配置信息
     */
    public function __construct(array $config = array());


    /**
     * 获取默认格式器
     *
     * @return FormatterInterface;
     */
    public function getDefaultFormatter();

    /**
     * 获取格式器
     *
     * @return FormatterInterface;
     */
    public function getFormatter();

    /**
     * 设置格式器
     *
     * @param FormatterInterface $formatter
     * @return mixed
     */
    public function setFormatter(FormatterInterface $formatter);

    /**
     * 日志处理方法
     *
     * @param array $record 日志数据
     * @return mixed
     */
    public function handle(array $record);

}
