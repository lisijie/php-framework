<?php
namespace Core\Event;

/**
 * 数据库事件
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Event;
 */
class DbEvent extends Event
{
	/**
	 * 查询SQL
	 * @var string
	 */
	private $sql;

	/**
	 * 查询参数
	 * @var array
	 */
	private $params = [];

	/**
	 * 是否主库查询
	 * @var bool
	 */
	private $fromMaster = false;

	/**
	 * 查询耗时
	 * @var float
	 */
	private $time = 0;

	/**
	 * 查询结果
	 * @var mixed
	 */
	private $result = null;

	public function __construct($sql, $params, $fromMaster, $time, $result = null)
	{
		$this->sql = $sql;
		$this->params = $params;
		$this->fromMaster = (bool) $fromMaster;
		$this->time = $time;
		$this->result = $result;
	}

	/**
	 * @return string
	 */
	public function getSql()
	{
		return $this->sql;
	}

	/**
	 * @return array
	 */
	public function getParams()
	{
		return $this->params;
	}

	/**
	 * @return boolean
	 */
	public function isFromMaster()
	{
		return $this->fromMaster;
	}
	/**
	 * @return float
	 */
	public function getTime()
	{
		return $this->time;
	}

	/**
	 * @return mixed
	 */
	public function getResult()
	{
		return $this->result;
	}
}