<?php
namespace Core\Logger\Handler;

use Core\Logger\Formatter\ArrayFormatter;
use PDO;

/**
 * DB日志处理器
 *
 * 配置选项:
 *  - dsn 数据库连接dsn
 *  - username 数据库用户名
 *  - password 数据库密码
 *  - table 表名
 *  - timeout 连接超时时间
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Logger
 */
class DbHandler extends AbstractHandler
{
    /**
     * @var PDO
     */
    private $db;

    public function init()
    {
        if (empty($this->config['dsn'])) {
            throw new \RuntimeException('缺少配置项: dsn');
        }
        if (empty($this->config['username'])) {
            throw new \RuntimeException('缺少配置项: username');
        }
        if (empty($this->config['table'])) {
            throw new \RuntimeException('缺少配置项: table');
        }
        if (!isset($this->config['timeout'])) {
            $this->config['timeout'] = 3;
        }
        if (!isset($this->config['password'])) {
            $this->config['password'] = '';
        }
    }

    public function getDefaultFormatter()
    {
        return new ArrayFormatter();
    }

    public function handleRecord(array $record)
    {
        $table = $this->config['table'];
        $row = $this->getFormatter()->format($record);
        $sql = "INSERT INTO {$table} (`datetime`, `channel`, `level`, `file`, `line`, `message`) VALUES (?, ?, ?, ?, ?, ?)";
        $sth = $this->getDb()->prepare($sql);
        return $sth->execute([
            $row['datetime'],
            $row['channel'],
            $row['level'],
            $row['file'],
            $row['line'],
            $row['message'],
        ]);
    }

    private function getDb()
    {
        if (!$this->db) {
            $this->db = new PDO($this->config['dsn'], $this->config['username'], $this->config['password'], [
                PDO::ATTR_TIMEOUT => $this->config['timeout'],
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        }
        return $this->db;
    }

}
