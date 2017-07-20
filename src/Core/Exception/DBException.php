<?php
namespace Core\Exception;

use Core\Lib\VarDumper;

/**
 * 数据库操作异常
 *
 * @author sijie.li
 * @package core\exceptions
 */
class DBException extends \PDOException
{
    private $sql;
    private $params;

    public function __construct($message = "", $code = 0, $sql = '', $params = [])
    {
        parent::__construct($message, (int)$code);
        $this->sql = $sql;
        $this->params = $params;
    }

    public function getSql()
    {
        return $this->sql;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function __toString()
    {
        $message = sprintf("exception '%s' with message '%s' in %s:%s\n", get_class($this), $this->message, $this->file, $this->line);
        $message .= "Sql: {$this->sql}\n";
        $message .= "Params: " . VarDumper::export($this->params) . "\n";
        $message .= "Stack trace:\n";
        $message .= $this->getTraceAsString();
        return $message;
    }
}