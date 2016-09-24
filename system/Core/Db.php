<?php

namespace Core;

use Core\Event\DbEvent;

/**
 * 数据库操作类
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core
 */
class Db extends Component
{
	/**
	 * 数据查询事件
	 */
	const EVENT_QUERY = 'query';

	/**
	 * 写模式
	 */
	const MODE_WRITE = 'write';

	/**
	 * 只读模式
	 */
	const MODE_READ = 'read';

	/**
	 * 自动判断
	 */
	const MODE_AUTO = 'auto';

	/**
	 * 查询次数
	 *
	 * @var int
	 */
	private static $queryCount = 0;

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
	private $options = [];

	/**
	 * 连接
	 *
	 * @var array
	 */
	private $connections = array();

	public function __construct($options)
	{
		if (!isset($options['charset'])) $options['charset'] = 'utf8';
		$this->options = $options;
	}

	/**
	 * 获取配置信息
	 *
	 * @param $name
	 * @return null
	 */
	public function getOption($name)
	{
		return isset($this->options[$name]) ? $this->options[$name] : null;
	}

	/**
	 * 获取数据库PDO连接
	 *
	 * $reload 参数用于重新创建PDO对象，相当于重新连接数据库。当你的程序作为命令行脚本运行时，有时可能会由于
	 * 数据库连接空闲时间超出MySQL设置的阈值（wait_timeout）而被MySQL主动断开（ 出现异常消息： MySQL server has
	 * gone away）。为了防止这种情况发生，需要在代码中显式调用 $db->getConnect('read', true) 重新连接数据库。
	 *
	 * @param string $mode 读写模式 write | read
	 * @param bool $reload 是否重新连接数据库
	 * @return \PDO
	 */
	public function getConnect($mode = self::MODE_WRITE, $reload = false)
	{
		if ($mode != self::MODE_READ || empty($this->options[$mode])) {
			$mode = self::MODE_WRITE;
		}
		if (!isset($this->connections[$mode]) || $reload) {
			$conf = $this->options[$mode];
			$this->connections[$mode] = new \PDO($conf['dsn'], $conf['username'], $conf['password'], [
				\PDO::ATTR_PERSISTENT => ($conf['pconnect'] ? true : false),
				\PDO::ATTR_TIMEOUT => 1,
				\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
			]);
		}
		return $this->connections[$mode];
	}

	/**
	 * 根据SQL语句返回数据库连接
	 *
	 * @param string $sql
	 * @return resource
	 */
	private function autoConn($sql = '')
	{
		$writeCommands = ['insert', 'update', 'delete', 'replace', 'alter', 'create', 'drop', 'rename', 'truncate'];
		if ($sql !== null) {
			$sql = explode(' ', trim((string)$sql));
		}
		if ($sql === null || !in_array(strtolower($sql[0]), $writeCommands)) {
			return $this->getConnect(self::MODE_READ);
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
	 * @param bool $fromMaster 是否强制从主库查询
	 * @return \PDOStatement
	 */
	public function query($sql, $data = [], $fromMaster = false)
	{
		self::$queryCount ++;
		$st = microtime(true);
		$sql = $this->sql($sql);
		$conn = $fromMaster ? $this->getConnect(self::MODE_WRITE) : $this->autoConn($sql);
		$stm = $conn->prepare($sql);
		$this->bindValue($stm, $data);
		$stm->execute();
		$this->trigger(self::EVENT_QUERY, new DbEvent($sql, $data, $fromMaster, microtime(true) - $st, $stm));
		return $stm;
	}

	/**
	 * 执行SQL并返回影响行数
	 *
	 * 用于INSERT、UPDATE、DELETE等写操作。
	 *
	 * @param string $sql SQL
	 * @param array $data 参数
	 * @return int 影响行数
	 */
	public function execute($sql, $data = [])
	{
		$stm = $this->query($sql, $data);
		return $stm->rowCount();
	}

	/**
	 * 查询一行记录
	 *
	 * @param string $sql SQL语句
	 * @param array $data 查询参数
	 * @param bool $fromMaster 是否强制从主库查询
	 * @return array|bool 失败返回false
	 */
	public function getRow($sql, $data = [], $fromMaster = false)
	{
		if (strpos(strtolower($sql), " limit ") === false) {
			$sql .= " LIMIT 1";
		}
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
	public function getOne($sql, $data = [], $index = 0, $fromMaster = false)
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
	public function select($sql, $data = [], $fromMaster = false)
	{
		$stm = $this->query($sql, $data, $fromMaster);
		return $stm->fetchAll(\PDO::FETCH_ASSOC);
	}

	/**
	 * 插入数据
	 *
	 * 可进行单条插入、批量插入、更新插入，当进行单条插入时，返回的是插入记录的自增主键ID，如果没有自增主键，则返回0。
	 * 如果是批量插入，则返回包含所有插入的ID数组。
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
		self::$queryCount ++;
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
		$this->trigger(self::EVENT_QUERY, new DbEvent($sql, $data, true, microtime(true) - $st, $stm));
		return $multi ? $ids : array_shift($ids);
	}

	/**
	 * 执行UPDATE查询并返回影响行数
	 *
	 * @param string $sql
	 * @param array $data
	 * @return int 影响行数
	 */
	public function update($sql, $data = [])
	{
		$stm = $this->query($sql, $data, true);
		return $stm->rowCount();
	}

	/**
	 * 执行DELETE查询并返回影响行数
	 *
	 * @param string $sql
	 * @param array $data
	 * @return int 影响行数
	 */
	public function delete($sql, $data = [])
	{
		$stm = $this->query($sql, $data, true);
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
		return $this->select($this->sql($sql), [], true);
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
