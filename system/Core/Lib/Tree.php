<?php

namespace Core\Lib;

class Tree
{
	/**
	 * 每个节点的数据
	 * @var array
	 */
	protected $data = array();
	/**
	 * 保存每个父节点下面的子节点ID列表
	 * @var array
	 */
	protected $child = array();
	/**
	 * 保存每个节点ID对应的父节点ID
	 * @var array
	 */
	protected $parent = array();
	/**
	 * 空格的HTML实体
	 * @var string
	 */
	protected $space = '&nbsp;';

	protected $icon = array('│', '├─ ', '└─ ');

	/**
	 * 增加一个节点
	 *
	 * @param int $id 节点ID
	 * @param int $parentId 父级ID
	 * @param array $data 节点数据
	 */
	public function addNode($id, $parentId, $data)
	{
		$this->data[$id] = (array)$data;
		$this->child[$parentId][] = $id;
		$this->parent[$id] = $parentId;
	}

	/**
	 * 根据节点ID返回节点数据
	 *
	 * @param int $id
	 * @return null|array
	 */
	public function getValue($id)
	{
		return isset($this->data[$id]) ? $this->data[$id] : null;
	}

	/**
	 * 获取某个节点的子节点ID列表
	 *
	 * @param int $id
	 * @return array
	 */
	public function getChildren($id)
	{
		return isset($this->child[$id]) ? $this->child[$id] : array();
	}

	/**
	 * 获取某个节点的子节点（包括子节点的子节点）ID列表
	 *
	 * @param int $id
	 * @return array
	 */
	public function getDeepChildren($id)
	{
		$list = array();
		if (isset($this->child[$id])) {
			foreach ($this->child[$id] as $cid) {
				$list[] = $cid;
				if (isset($this->child[$cid])) {
					$list = array_merge($list, $this->getDeepChildren($cid));
				}
			}
		}
		return $list;
	}

	/**
	 * 返回某个节点的父节点ID
	 *
	 * @param int $id
	 * @return int
	 */
	public function getParentId($id)
	{
		return $this->parent[$id];
	}

	/**
	 * 返回各层父ID列表
	 *
	 * @param int $id
	 * @return array
	 */
	public function getDeepParentIds($id)
	{
		$list = array();
		if ($this->parent[$id] > 0) {
			$list[] = $pid = $this->parent[$id];
			if ($this->parent[$pid] > 0) {
				$list = array_merge($list, $this->getDeepParentIds($pid));
			}
		}
		return $list;
	}

	/**
	 * 获取某个节点下的子节点列表
	 *
	 * @param array $list
	 * @param int $root
	 * @return array
	 */
	public function getList(&$list, $root = 0)
	{
		if (isset($this->child[$root])) {
			foreach ($this->child[$root] as $id) {
				$list[] = $id;
				if (isset($this->child[$id])) {
					$this->getList($list, $id);
				}
			}
		}
	}

	/**
	 * 获取某个节点的层级数
	 *
	 * @param int $id
	 * @return int
	 */
	public function getLayer($id) {
		$n = 0;
		$nodeId = $id;
		while ($this->parent[$nodeId] > 0) {
			$nodeId = $this->parent[$nodeId];
			$n ++;
		}
		return $n;
	}

	public function getLayerHtml($id, $space = '', $s = '├─&nbsp;')
	{
		$layer = $this->getLayer($id);
		if ($layer > 0) {
			return str_repeat($space, $layer) . $s;
		}
		return '';
	}

	/**
	 * 返回一个树形结构HTML
	 *
	 * $str = '<option value=\"$id\" $selected>$spacer$name</option>'
	 *
	 * @param int $rootId 根分类ID
	 * @param int $selectedId 选中的ID
	 * @param string $str 每个节点拼接的HTML格式
	 * @param string $groupStr
	 * @param string $selStr
	 * @return string
	 */
	public function makeTreeHtml($rootId, $selectedId, $str, $groupStr = '', $selStr = 'selected')
	{
		$childIds = $this->getChildren($rootId);
		$_count = count($childIds);
		$result = '';
		if ($_count > 0) {
			foreach ($childIds as $_key => $_id) {
				$data = $this->getValue($_id);

				if ($_count == ($_key + 1)) {
					$spacer = $this->getLayerHtml($_id, '|&nbsp;&nbsp;&nbsp;&nbsp;', $this->icon[2]); //└─
				} else {
					$spacer = $this->getLayerHtml($_id, '|&nbsp;&nbsp;&nbsp;&nbsp;', $this->icon[1]); //├─
				}

				$selected = ($_id == $selectedId) ? $selStr : '';
				@extract($data);
				$string = '';

				if ($groupStr && $this->getParentId($_id) > 0) {
					eval("\$string = \"{$groupStr}\";");
				} else {
					eval("\$string = \"{$str}\";");
				}

				$result .= $string;
				if (isset($this->child[$_id])) {
					$result .= $this->makeTreeHtml($_id, $selectedId, $str, $groupStr, $selStr);
				}
			}
		}
		return $result;
	}

}
