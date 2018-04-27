<?php

namespace Core\Logger\Handler;

use Core\Logger\Formatter\FormatterInterface;
use Core\Logger\InvalidArgumentException;

/**
 * 抽象日志处理器
 *
 * @package Core\Logger
 * @author lisijie <lsj86@qq.com>
 */
abstract class AbstractHandler implements HandlerInterface
{
    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var int
     */
    private $level = 0;

    /**
     * 格式器
     *
     * @var \Core\Logger\Formatter\FormatterInterface;
     */
    protected $formatter;

    public function __construct(array $config = [])
    {
        if (isset($config['formatter'])) {
            $formatterClass = $config['formatter'];
            if (!class_exists($formatterClass)) {
                throw new InvalidArgumentException('找不到日志格式化类: ' . $formatterClass);
            }
            $this->formatter = new $formatterClass();
        }
        if (isset($config['date_format'])) {
            $this->getFormatter()->setDateFormat($config['date_format']);
        }
        if (isset($config['level'])) {
            $this->level = (int)$config['level'];
        }
        $this->config = $config;
        $this->init();
    }

    abstract function init();

    abstract function getDefaultFormatter();

    public function handle(array $record)
    {
        if ($record['level'] < $this->level) {
            return false;
        }
        return $this->handleRecord($record);
    }

    abstract function handleRecord(array $record);

    public function setFormatter(FormatterInterface $formatter)
    {
        $this->formatter = $formatter;
    }

    public function getFormatter()
    {
        if (!$this->formatter) {
            $this->formatter = $this->getDefaultFormatter();
        }
        return $this->formatter;
    }
}
