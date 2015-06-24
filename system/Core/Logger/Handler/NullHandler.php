<?php

namespace Core\Logger\Handler;

/**
 * 空日志处理器
 *
 * 所有日志将会被丢弃
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Logger
 */
class NullHandler extends AbstractHandler
{
    public function __construct(array $config = array())
    {

    }

    public function getDefaultFormatter()
    {

    }

    public function handle(array $record)
    {

    }
}
