<?php

namespace Core\Lib;

class Tree
{
    protected $_data = array();
    protected $_child = array();
    protected $_parent = array();
    protected $_layer = array();
    protected $_return = '';
    protected $_icon = array('│', '├─ ', '└─ ');
    protected $_nbsp = '&nbsp;';

    /**
     * 增加一个节点
     *
     * @param int $id 节点ID
     * @param int $parentid 父级ID
     * @param array $data 节点数据
     */
    public function addNode($id, $parentid, $data)
    {
        $this->_data[$id] = (array)$data;
        $this->_child[$parentid][] = $id;
        $this->_parent[$id] = $parentid;
        if (!isset($this->_layer[$parentid])) {
            $this->_layer[$id] = 0;
        } else {
            $this->_layer[$id] = $this->_layer[$parentid] + 1;
        }
    }

    /**
     * 根据节点ID返回节点数据
     *
     * @param int $id
     */
    public function getValue($id)
    {
        return $this->_data[$id];
    }

    /**
     * 获取某个节点的子节点列表
     *
     * @param int $id
     * @return array
     */
    public function getChilds($id)
    {
        return isset($this->_child[$id]) ? $this->_child[$id] : array();
    }

    /**
     * 获取某个节点的子节点（包括子节点的子节点）列表
     *
     * @param int $id
     * @return array
     */
    public function getDeepChilds($id)
    {
        $list = array();
        if (isset($this->_child[$id])) {
            foreach ($this->_child[$id] as $cid) {
                $list[] = $cid;
                if (isset($this->_child[$cid])) {
                    $list = array_merge($list, $this->getDeepChilds($cid));
                }
            }
        }
        return $list;
    }

    /**
     * 返回某个节点的父节点ID
     * @param int $id
     * @return int
     */
    public function getParent($id)
    {
        return $this->_parent[$id];
    }

    /**
     * 返回各层父ID列表
     *
     * @param int $id
     * @return array
     */
    public function getDeepParents($id)
    {
        $list = array();
        if ($this->_parent[$id] > 0) {
            $list[] = $pid = $this->_parent[$id];
            if ($this->_parent[$pid] > 0) {
                $list = array_merge($list, $this->getDeepParents($pid));
            }
        }
        return $list;
    }

    /**
     * 获取某个节点下的子节点列表
     * @param array $list
     * @param int $root
     * @return array
     */
    public function getList(&$list, $root = 0)
    {
        if (isset($this->_child[$root])) {
            foreach ($this->_child[$root] as $id) {
                $list[] = $id;
                if (isset($this->_child[$id])) {
                    $this->getList($list, $id);
                }
            }
        }
    }

    public function getLayer($id, $s = '├─&nbsp;')
    {
        if ($this->_layer[$id] > 0) {
            return str_repeat('&nbsp;', $this->_layer[$id] * 4) . $s;
        }
        return '';
    }

    public function getTree($root, $sid, $str, $groupstr = '', $selstr = 'selected="selected"')
    {
        $_childs = $this->getChilds($root);
        $_count = count($_childs);
        if ($_count > 0) {
            foreach ($_childs as $_key => $_id) {
                $data = $this->getValue($_id);
                $layer = $this->_layer[$_id];
                $spacer = '';
                if ($layer > 0) {
                    $spacer = str_repeat($this->_nbsp, $layer * 4);
                    if ($_count == ($_key + 1)) {
                        $spacer .= $this->_icon[2]; //└─
                    } else {
                        $spacer .= $this->_icon[1]; //├─
                    }
                }
                $selected = ($_id == $sid) ? $selstr : '';
                @extract($data);
                $parentid > 0 && $groupstr ? eval("\$string = \"{$groupstr}\";") : eval("\$string = \"{$str}\";");
                $this->return .= $string;
                if ($this->_child[$_id]) {
                    $this->getTree($_id, $sid, $str, $adds);
                }
            }
        }
        return $this->return;
    }

}
