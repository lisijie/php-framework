<?php

namespace Core\Logger\Handler;

use Core\Logger\Formatter\FormatterInterface;

/**
 * 抽象日志处理器
 *
 * @package Core\Logger
 * @author lisijie <lsj86@qq.com>
 */
abstract class AbstractHandler implements HandlerInterface
{
    /**
     * 格式器
     *
     * @var \Core\Logger\Formatter\FormatterInterface;
     */
    protected $formatter;

    abstract function __construct(array $config = []);

    abstract function getDefaultFormatter();

    abstract function handle(array $record);

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
