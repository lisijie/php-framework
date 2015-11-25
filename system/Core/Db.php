<?php

namespace Core;

/**
 * 数据库操作类
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core
 */
class Db
{

    const TAG_BEFORE_QUERY = 'before_query';
    const TAG_AFTER_QUERY  = 'after_query';

    /**
     * 慢查询次数
     *
     * @var int
     */
    static $slowCount = 0;

    /**
     * 查询次数
     *
     * @var int
     */
    static $queryCount = 0;

    /**
     * 事务数量
     *
     * @var int
     */
    private $transCount = 0;

    /**
     * 数据配置信息
     * @var array
     */
    private $options = array();

    /**
     * 连接
     *
     * @var array
     */
    private $connection = array();

    /**
     * hooks
     *
     * @var array
     */
    private $hooks = array();

    public function __construct($options)
    {
        if (!isset($options['charset'])) $options['charset'] = 'utf8';
        $this->options = $options;
    }

    /**
     * 添加钩子函数
     *
     * @param string $tag
     * @param callable $func
     */
    public function addHook($tag, $func)
    {
        $this->hooks[$tag][] = $func;
    }

    /**
     * 执行钩子函数
     *
     * @param string $tag
     * @param array $data
     */
    private function runHook($tag, array $data)
    {
        static::$queryCount ++;
        if (isset($this->options['slow_log']) && $data['time'] > $this->options['slow_log']) {
            static::$slowCount ++;
        }
        if (isset($this->hooks[$tag]) && !empty($this->hooks[$tag])) {
            foreach ($this->hooks[$tag] as $func) {
                if ($func instanceof \Closure) {
                    $func($data);
                } elseif (is_callable($func)) {
                    call_user_func($func, $data);
                }
            }
        }
    }

    /**
     * 获取数据库PDO连接
     *
     * @param string $mode 读写模式 write | read
     * @return \PDO
     */
    public function getConnect($mode = 'write')
    {
        if ($mode != 'read' || empty($this->options[$mode])) {
            $mode = 'write';
        }
        if (!isset($this->connection[$mode])) {
            $conf = $this->options[$mode];
            $this->connection[$mode] = new \PDO($conf['dsn'], $conf['username'], $conf['password'], array(
                \PDO::ATTR_PERSISTENT => ($conf['pconnect'] ? true : false),
                \PDO::ATTR_TIMEOUT => 1,
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ));
        }
        return $this->connection[$mode];
    }

    /**
     * 根据SQL语句返回数据库连接
     *
     * @param string $sql
     * @return resource
     */
    private function autoConn($sql = '')
    {
        $write_command = array('insert', 'update', 'delete', 'replace', 'alter', 'create', 'drop', 'rename', 'truncate');
        if ($sql !== null) {
            $sql = explode(' ', trim((string)$sql));
        }
        if ($sql === null || !in_array(strtolower($sql[0]), $write_command)) {
            return $this->getConnect('read');
        }
        return $this->getConnect();
    }

    /**
     * 把一个值绑定到一个参数
     *
     * 绑定一个值到用作预处理的 SQL 语句中的对应命名占位符或问号占位符。
     *
     * @param object $stm
     * @param array $data
     * @return bool
     */
    private function bindValue(&$stm, $data)
    {
        if (!is_array($data)) return false;
        foreach ($data as $k => $v) {
            $k = is_numeric($k) ? $k + 1 : ':' . $k;
            $stm->bindValue($k, $v);
        }
        return true;
    }

    /**
     * 替换SQL语句的表前缀
     *
     * @param string $sql
     * @return string
     */
    private function sql($sql)
    {
        return str_replace('#table_', $this->options['prefix'], $sql);
    }

    /**
     * 执行SQL并返回PDOStatement
     *
     * @param string $sql SQL语句
     * @param array $data 参数
     * @param string $mode 读写方式
     * @return \PDOStatement
     */
    public function query($sql, $data = array(), $mode = 'auto')
    {
        $st = microtime(true);
        $sql = $this->sql($sql);
        $conn = $mode == 'auto' ? $this->autoConn($sql) : $this->getConnect($mode);
        $stm = $conn->prepare($sql);
        $this->bindValue($stm, $data);
        $stm->execute();
        $this->runHook(static::TAG_AFTER_QUERY, array(
            'sql'    => $sql,
            'data'   => $data,
            'method' => 'query',
            'mode'   => $mode,
            'time'   => microtime(true) - $st,
            'result' => $stm
        ));
        return $stm;
    }

    /**
     * 执行SQL并返回影响行数
     *
     * @param string $sql SQL
     * @return int 影响行数
     */
    public function execute($sql)
    {
        $st = microtime(true);
        $conn = $this->getConnect();
        $sql = $this->sql($sql);
        $ret = $conn->exec($sql);
        $this->runHook(static::TAG_AFTER_QUERY, array(
            'sql'    => $sql,
            'data'   => array(),
            'method' => 'execute',
            'mode'   => 'write',
            'time'   => microtime(true) - $st,
            'result' => $ret,
        ));
        return $ret;
    }

    /**
     * 查询一行记录
     *
     * @param string $sql SQL语句
     * @param array $data 查询参数
     * @param bool $fromMaster 是否强制从主库查询
     * @return array|bool 失败返回false
     */
    public function getRow($sql, $data = array(), $fromMaster = false)
    {
        $stm = $this->query($sql, $data, $fromMaster);
        return $stm->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * 获取第一条记录某个字段的值
     *
     * @param string $sql 查询SQL
     * @param array $data 绑定参数
     * @param int $index 第几个字段，默认0为第一个
     * @param bool $fromMaster 是否强制从主库查询
     * @return string|bool 字段值，失败返回false
     */
    public function getOne($sql, $data = array(), $index = 0, $fromMaster = false)
    {
        $result = $this->getRow($sql, $data, $fromMaster);
        return (is_array($result) ? (isset($result[$index]) ? $result[$index] : array_shift($result)) : $result);
    }

    /**
     * 执行查询SQL并返回所有记录
     *
     * @param string $sql SQL语句
     * @param array $data 查询参数
     * @param bool $fromMaster 是否强制从主库查询
     * @return array 查询结果
     */
    public function select($sql, $data = array(), $fromMaster = false)
    {
        $stm = $this->query($sql, $data, $fromMaster);
        return $stm->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * 插入数据
     *
     * @param string $table 表名
     * @param array $data 要插入的数据
     * @param bool $replace 是否替换插入
     * @param bool $multi 是否批量插入
     * @param bool $ignore 是否忽略错误
     * @return string|array 最后插入的ID，批量插入的话返回所有ID
     */
    public function insert($table, $data, $replace = false, $multi = false, $ignore = false)
    {
        if (!$multi) $data = array($data);
        $st = microtime(true);
        $fields = '`' . implode('`,`', array_keys($data[0])) . '`'; //字段
        $values = '(' . str_repeat('?,', count($data[0]) - 1) . '?)';
        $method = $replace ? 'REPLACE ' : 'INSERT';
        $ignore = (!$replace && $ignore) ? 'IGNORE' : '';
        $sql = $this->sql("{$method} {$ignore} INTO {$table} ({$fields}) VALUES {$values}");
        $conn = $this->getConnect();
        $stm = $conn->prepare($sql);
        $ids = array();
        foreach ($data as $row) {
            $stm->execute(array_values($row));
            $ids[] = $conn->lastInsertId();
        }
        $this->runHook(static::TAG_AFTER_QUERY, array(
            'sql'    => $sql,
            'data'   => $data,
            'method' => 'insert',
            'mode'   => 'write',
            'time'   => microtime(true) - $st,
            'result' => $ids
        ));
        return $multi ? $ids : array_shift($ids);
    }

    /**
     * 执行UPDATE查询并返回影响行数
     *
     * @param string $sql
     * @param array $data
     * @return int 影响行数
     */
    public function update($sql, $data = array())
    {
        $stm = $this->query($sql, $data, 'write');
        return $stm->rowCount();
    }

    /**
     * 执行DELETE查询并返回影响行数
     *
     * @param string $sql
     * @param array $data
     * @return int 影响行数
     */
    public function delete($sql, $data = array())
    {
        $stm = $this->query($sql, $data, 'write');
        return $stm->rowCount();
    }

    /**
     * 列出某个表的字段信息
     *
     * @param string $table
     * @param string $pattern
     * @return array
     */
    public function getFields($table, $pattern = null)
    {
        $sql = "SHOW COLUMNS FROM `$table`";
        if ($pattern) $sql .= " LIKE '%{$pattern}%'";
        return $this->select($this->sql($sql), array(), true);
    }

    /**
     * 列出指定数据库的所有数据表
     *
     * @param string $pattern
     * @return array
     */
    public function getTables($pattern = null)
    {
        $tables = array();
        $sql = "SHOW TABLES" . ($pattern ? " LIKE '%{$pattern}%'" : '');
        $result = $this->select($sql, array(), true);
        foreach ($result as $r) {
            foreach ($r as $table) $tables[] = $table;
        }
        return $tables;
    }

    /**
     * 获取配置信息
     *
     * @param string $name
     * @return array|null
     */
    public function getOption($name)
    {
        return isset($this->options[$name]) ? $this->options[$name] : null;
    }

    /**
     * 返回带前缀的表名
     *
     * @param string $table 表名
     * @return string
     */
    public function table($table)
    {
        return $this->options['prefix'] . $table;
    }

    /**
     * 开始事务(主库)
     *
     * @see PDO::beginTransaction()
     */
    public function beginTransaction()
    {
        if ($this->transCount == 0) {
            $this->getConnect('write')->beginTransaction();
        }
        $this->transCount++;
    }

    /**
     * 提交事务(主库)
     *
     * @see PDO::commit()
     */
    public function commit()
    {
        if ($this->transCount == 0) return;
        $this->transCount--;
        if ($this->transCount == 0) {
            $this->getConnect('write')->commit();
        }
    }

    /**
     * 回滚事务(主库)
     *
     * @see PDO::rollBack()
     */
    public function rollBack()
    {
        if ($this->transCount == 0) return;
        $this->transCount--;
        if ($this->transCount == 0) {
            $this->getConnect('write')->rollBack();
        }
    }

    /**
     * 转义用户输入的特殊字符
     *
     * @param string $string
     * @param int $type
     * @return string
     */
    public function quote($string, $type = \PDO::PARAM_STR)
    {
        return $this->getConnect()->quote($string, $type);
    }

}
