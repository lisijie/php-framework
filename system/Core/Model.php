<?php

namespace Core;

/**
 * 基于表的模型基类
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core
 */
class Model
{

    /**
     * DB对象
     *
     * @var \Core\Db
     */
    protected $db;

    /**
     * 数据库配置
     *
     * @var string
     */
    protected $dbNode = 'default';

    /**
     * 表名
     * @var string
     */
    protected $tableName = '';

    /**
     * 字段列表
     * @var array
     */
    protected $fields = array();

    private final function __construct()
    {
        $this->db = \App::db($this->dbNode);
        $this->init();
    }

    /**
     * 获取模型单例
     *
     * @return static
     */
    public static function getInstance()
    {
        static $instance;
        if (!$instance) {
            $instance = new static();
        }
        return $instance;
    }

    /**
     * 子类初始化方法
     *
     * 指定DB连接，表名等操作
     */
    protected function init()
    {

    }

    /**
     * 表统计
     *
     * @param array $filter
     * @return int
     */
    public function count(array $filter = array())
    {
        $sql = "SELECT COUNT(*) FROM " . $this->db->table($this->tableName);
        if (!empty($filter)) $sql .= " WHERE " . $this->parseFilter($filter);
        return intval($this->db->getOne($sql));
    }

    /**
     * 查询数据
     *
     * @param array $fields 查询字段
     * @param array $filter 查询条件
     * @param array $order 排序条件
     * @param int $limit 查询数量
     * @param int $offset 偏移量
     * @return array
     */
    public function select(array $fields = null, array $filter = null, array $order = null, $limit = 0, $offset = 0)
    {
        $table = $this->db->table($this->tableName);
        $fields = empty($fields) ? '*' : implode(',', $fields);
        $sql = "SELECT {$fields} FROM {$table}";
        if (!empty($filter)) {
            $sql .= " WHERE " . $this->parseFilter($filter);
        }
        if (!empty($order)) {
            $orderSql = array();
            foreach ($order as $key => $val) {
                $orderSql[] = "{$key} " . (strtolower($val) == 'asc' ? 'ASC' : 'DESC');
            }
            $sql .= " ORDER BY " . implode(', ', $orderSql);
        }
        if ($limit > 0) $sql .= $offset > 0 ? " LIMIT $offset, $limit" : " LIMIT $limit";
        return $this->db->select($sql);
    }

    /**
     * 分页查询
     *
     * @param array $fields 查询字段
     * @param array $filter 查询条件
     * @param array $order 排序条件
     * @param int $page 页码
     * @param int $size 每页数量
     * @return array
     */
    public function page(array $fields, array $filter, array $order, $page = 1, $size = 20)
    {
        $offset = 0;
        if ($page > 0 && $size > 0) {
            $page = max(intval($page), 1);
            $size = max(intval($size), 1);
            $offset = ($page - 1) * $size;
        }
        return $this->select($fields, $filter, $order, $size, $offset);
    }

    /**
     * 插入记录
     *
     * 返回最后插入的ID
     *
     * @param array $data 插入数据
     * @param bool $replace 是否替换插入
     * @param bool $multi 是否批量插入
     * @param bool $ignore 是否忽略重复
     * @return int
     */
    public function insert($data, $replace = false, $multi = false, $ignore = false)
    {
        $table = $this->db->table($this->tableName);
        return $this->db->insert($table, $data, $replace, $multi, $ignore);
    }

    /**
     * 查询一行记录
     *
     * @param array $filter 过滤条件
     * @param array $fields 字段
     * @return array
     */
    public function getRow(array $filter = null, array $fields = array())
    {
        if ($fields) {
            $fields = '`' . implode('`,`', $fields) . '`';
        } else {
            $fields = '*';
        }
        $sql = "SELECT {$fields} FROM " . $this->db->table($this->tableName);
        if (!empty($filter)) {
            $sql .= " WHERE " . $this->parseFilter($filter);
        }
        $sql .= " LIMIT 1";
        return $this->db->getRow($sql);
    }

    /**
     * 更新记录
     *
     * @param array $data 更新数据
     * @param array $filter 更新条件
     * @return int
     */
    public function update($data, array $filter)
    {
        $table = $this->db->table($this->tableName);
        $sql = "UPDATE {$table} SET ";
        $split = '';
        foreach ($data as $key => $val) {
            $sql .= "{$split}`{$key}` = :{$key}";
            $split = ', ';
        }
        if (!empty($filter)) {
            $sql .= " WHERE " . $this->parseFilter($filter);
        }
        return $this->db->update($sql, $data);
    }

    /**
     * 删除记录
     *
     * @param array $filter 条件
     * @return int 返回影响行数
     */
    public function delete(array $filter)
    {
        $table = $this->db->table($this->tableName);
        $sql = "DELETE FROM {$table} ";
        if (!empty($filter)) {
            $sql .= " WHERE " . $this->parseFilter($filter);
        }

        return $this->db->delete($sql);
    }

	/**
	 * 字段自增
	 *
	 * $this->increment(['a'=>1, 'b'=>-2], ['id'=>1])
	 *
	 * @param array $data 字段和值
	 * @param array $filter 条件
	 * @return int 影响行数
	 */
    public function increment(array $data, array $filter)
    {
	    $table = $this->db->table($this->tableName);
	    $sql = "UPDATE {$table} SET ";
	    foreach ($data as $key => $val) {
		    $sql .= " `{$key}` = `{$key}` + " . intval($val) . ",";
	    }
	    $where = $this->parseFilter($filter);
	    $sql = rtrim($sql, ',');
	    if ($where) {
		    $sql .= " WHERE {$where}";
	    }
	    return $this->db->execute($sql);
    }

    /**
     * 返回表名
     *
     * @return string 表名
     */
    public function getTable()
    {
        return $this->tableName;
    }

    public function __toString()
    {
        return $this->tableName;
    }

    /**
     * 使用指定字段重新索引数组
     *
     * @param array $data
     * @param $idx
     * @return array
     */
    public function index(array $data, $idx)
    {
        if (empty($data) || !isset($data[0][$idx])) {
            return $data;
        }
        $tmp = array();
        foreach ($data as $row) {
            $tmp[$row[$idx]] = $row;
        }
        return $tmp;
    }

    /**
     * 将数组解析成SQL
     *
     * @param array $filter
     * @return string
     */
    protected function parseFilter(array $filter)
    {
        $where = array();
        foreach ($filter as $field => $val) {
            if (($pos = strrpos($field, '__')) > 0) {
                $op = substr($field, $pos + 2);
                $field = substr($field, 0, $pos);
                switch ($op) {
                    case 'gt': //大于
                        $where[] = "`{$field}` > " . $this->db->quote($val);
                        break;
                    case 'gte': //大于等于
                        $where[] = "`{$field}` >= " . $this->db->quote($val);
                        break;
                    case 'lt': //小于
                        $where[] = "`{$field}` < " . $this->db->quote($val);
                        break;
                    case 'lte': //小于等于
                        $where[] = "`{$field}` <= " . $this->db->quote($val);
                        break;
                    case 'like': //LIKE ‘%%’
                        $where[] = "`{$field}` LIKE " . $this->db->quote("%{$val}%");
                        break;
                    case 'startswith': //LIKE 'xxx%'
                        $where[] = "`{$field}` LIKE " . $this->db->quote("{$val}%");
                        break;
                    case 'endswith': //LIKE '%xxx'
                        $where[] = "`{$field}` LIKE " . $this->db->quote("%{$val}");
                        break;
                    case 'between': //between 'a' AND 'b'
                        $where[] = "`{$field}` BETWEEN " . $this->db->quote($val[0]) . " AND " . $this->db->quote($val[1]);
                        break;
                }
            } elseif (is_array($val)) {
                foreach ($val as $k => $v) {
                    $val[$k] = $this->db->quote($v);
                }
                $where[] = "`{$field}` IN (" . implode(',', $val) . ")";
            } else {
                $where[] = "`{$field}` = " . $this->db->quote($val);
            }
        }
        return implode(' AND ', $where);
    }
}
