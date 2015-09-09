<?php

namespace Core\Logger;

use Core\Logger\Handler\HandlerInterface;
use Core\Logger\Handler\NullHandler;

/**
 * 日志处理类
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Logger
 */
class Logger implements LoggerInterface
{
    const FATAL   = 5;
    const ERROR   = 4;
    const WARN    = 3;
    const INFO    = 2;
    const DEBUG   = 1;

    /**
     * 日志等级对应名称映射
     *
     * @var array
     */
    protected $levels = array(
        self::FATAL   => 'FATAL',
        self::ERROR   => 'ERROR',
        self::WARN    => 'WARN',
        self::INFO    => 'INFO',
        self::DEBUG   => 'DEBUG',
    );

    /**
     * 日志名称
     * @var string
     */
    protected $name;

    /**
     * 时区
     * @var \DateTimeZone
     */
    protected $timeZone;

    /**
     * 日志处理器
     * @var object
     */
    protected $handlers = array();

    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * 获取名称
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * 设置日志处理器
     *
     * @param HandlerInterface $handler
     * @param $level
     * @throws InvalidArgumentException
     */
    public function setHandler(HandlerInterface $handler, $level)
    {
        if (!isset($this->levels[$level])) {
            throw new InvalidArgumentException('日志级别无效');
        }
        $this->handlers[] = array($level, $handler);
    }

    /**
     * 设置时区
     *
     * @param \DateTimeZone $timeZone
     */
    public function setTimeZone(\DateTimeZone $timeZone)
    {
        $this->timeZone = $timeZone;
    }

    /**
     * 发生危险错误
     *
     * 例如: 应用程序组件不可用,意想不到的异常。
     *
     * @param string $message
     * @param array $context
     * @return null|void
     */
    public function fatal($message, array $context = array())
    {
        $this->log(self::FATAL, $message, $context);
    }

    /**
     * 运行时错误
     *
     * 例如: 用户非法操作,一般不需要立即采取行动,但需要记录和监控
     *
     * @param string $message
     * @param array $context
     * @return null|void
     */
    public function error($message, array $context = array())
    {
        $this->log(self::ERROR, $message, $context);
    }

    /**
     * 表明会出现潜在错误的情形
     *
     * 例如: 使用一个已经废弃的API,虽然没有错误,但应该提醒用户修正
     *
     * @param string $message
     * @param array $context
     * @return null|void
     */
    public function warn($message, array $context = array())
    {
        $this->log(self::WARN, $message, $context);
    }

    /**
     * 记录程序运行时的相关信息
     *
     * 例如: 用户登录,SQL记录
     *
     * @param string $message
     * @param array $context
     * @return null|void
     */
    public function info($message, array $context = array())
    {
        $this->log(self::INFO, $message, $context);
    }

    /**
     * 调试信息
     *
     * 主要用于开发期间记录调试信息，线上一般不开启
     *
     * @param string $message
     * @param array $context
     * @return null|void
     */
    public function debug($message, array $context = array())
    {
        $this->log(self::DEBUG, $message, $context);
    }

    /**
     * 记录日志
     *
     * @param int $level 日志级别
     * @param string $message 内容
     * @param array $context 上下文
     * @return null|void
     * @throws InvalidArgumentException
     */
    public function log($level, $message, array $context = array())
    {
        if (!isset($this->levels[$level])) {
            throw new InvalidArgumentException('日志等级无效:' . $level);
        }

        if (empty($this->handlers)) {
            $this->setHandler(new NullHandler(), self::DEBUG);
        }

        if (!$this->timeZone) {
            $this->timeZone = new \DateTimeZone(date_default_timezone_get() ? : 'UTC');
        }

        $record = array(
            'message' => (string)$message,
            'context' => $context,
            'level' => $level,
            'level_name' => $this->levels[$level],
            'channel' => $this->name,
            'datetime' => \DateTime::createFromFormat('U.u', sprintf('%.6F', microtime(true)), $this->timeZone)->setTimezone($this->timeZone),
            'extra' => array(),
        );

        foreach ($this->handlers as $value) {
            list($lv, $handler) = $value;
            if ($lv <= $level) {
                $handler->handle($record);
            }
        }
    }
}
