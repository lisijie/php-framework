<?php

namespace Core\Logger\Handler;

use Core\Logger\Formatter\LineFormatter;

/**
 * 文件日志处理器
 *
 * 配置选项:
 *  - savepath 日志存放目录
 *  - filesize 日志文件切割大小，单位M，0为不限制
 *  - filename 日志文件名格式，默认{Y}{m}{d}.log
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Logger
 */
class FileHandler extends AbstractHandler
{

    /**
     * 日志文件存放目录，默认: DATA_PATH/logs
     * @var string
     */
    private $savePath;

    /**
     * 单个日志文件大小/MB，默认:10M
     * @var int
     */
    private $fileSize = 0;

    /**
     * 日志文件格式
     *
     * 默认为每天一个文件，如要按日志级别和日期分文件，可配置为：{level}_{Y}{m}{d}.php
     *
     * 可用变量:
     *  - {level} 日志级别
     *  - {Y} 年份，如 2014
     *  - {m} 数字表示的月份，有前导零，01 到 31
     *  - {d} 月份中的第几天，有前导零的 2 位数字，01 到 31
     *  - {H} 小时，24 小时格式，有前导零
     *
     * @var string
     */
    private $fileName = '{Y}{m}{d}.log';

    public function __construct(array $config = array())
    {
        if (empty($config['savepath'])) {
            throw new \RuntimeException('Lib\Logger\Handler\FileHandler 缺少配置项: savepath');
        }
        $this->savePath = $config['savepath'];
        if (!is_dir($this->savePath) && !@mkdir($this->savePath, 0755, true)) {
            throw new \RuntimeException('Lib\Logger\Handler\FileHandler 日志目录创建失败: ' . $this->savePath);
        }
        if (isset($config['filesize']) && is_numeric($config['filesize'])) {
            $this->fileSize = max(1, intval($config['filesize'])) * 1024 * 1024;
        }
        if (isset($config['filename']) && $config['filename']) {
            $this->fileName = $config['filename'];
        }
    }

    public function getDefaultFormatter()
    {
        return new LineFormatter();
    }

    public function handle(array $record)
    {
        $message = $this->getFormatter()->format($record);

        $fileName = str_replace(
            array('{level}', '{Y}', '{m}', '{d}', '{H}'),
            array($record['level_name'], date('Y'), date('m'), date('d'), date('H')),
            $this->fileName);

        $fileName = $this->savePath . DIRECTORY_SEPARATOR . $fileName;

        if ($this->fileSize > 0 && is_file($fileName) && filesize($fileName) > $this->fileSize) {
            $info = pathinfo($fileName);
            $newName = $info['dirname'] . DIRECTORY_SEPARATOR . $info['filename'] . '_' . time() . '.' . $info['extension'];
            rename($fileName, $newName);
        }

        if (!is_file($fileName) && substr($fileName, -4) == '.php') {
            $message = "<?php exit;?>\n{$message}";
        }

        return error_log($message, 3, $fileName);
    }

}
