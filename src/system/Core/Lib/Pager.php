<?php
/**
 * 分页类
 *
 * @author lisijie <lsj86@qq.com>
 */

namespace Core\Lib;

class Pager
{

    private $params = array();
    private $templates = array(
        'prev_page' => '<li><a href="%s">&laquo;</a></li>',
        'prev_page_disabled' => '<li class="disabled"><span>&laquo;</span></li>',
        'next_page' => '<li><a href="%s">&raquo;</a></li>',
        'next_page_disabled' => '<li class="disabled"><span>&raquo;</span></li>',
        'page_item' => '<li><a href="%s">%s</a></li>',
        'page_item_active' => '<li class="active"><span>%s</span></li>',
        'wrapper' => '<ul class="pagination pagination-sm">%s</ul>',
    );

    /**
     * 构造方法
     *
     * @param int $curPage 当前页码
     * @param int $pageSize 每页数量
     * @param int $totalNum 总记录数
     * @param string $route 路由地址
     * @param array $params 路由参数
     */
    public function __construct($curPage, $pageSize, $totalNum, $route = CUR_ROUTE, $params = array())
    {
        $this->params = array(
            'curPage' => $curPage,
            'pageSize' => $pageSize,
            'totalNum' => $totalNum,
            'linkNum' => 10,
            'offset' => 5,
            'route' => $route,
            'params' => $params,
        );
    }

    /**
     * 设置参数
     *
     * @param string $name 参数名
     * @param mixed $value 参数值
     */
    public function setParam($name, $value)
    {
        $this->params[$name] = $value;
    }

    /**
     * 获取参数值
     *
     * @param string $name
     * @return mixed
     */
    public function getParam($name)
    {
        return isset($this->params[$name]) ? $this->params[$name] : null;
    }

    /**
     * 设置模板
     *
     * @param array $templates
     */
    public function setTemplates(array $templates)
    {
        $this->templates = array_merge($this->templates, $templates);
    }

    /**
     * 生成URL
     * @param int $page 页码
     * @return string
     */
    private function makeUrl($page)
    {
        $params = $this->params['params'];
        $params['page'] = intval($page);
        return URL($this->params['route'], $params);
    }

    /**
     * 返回显示分页HTML
     */
    public function makeHtml()
    {
        $totalNum = intval($this->params['totalNum']);
        $pageSize = intval($this->params['pageSize']);
        $linkNum = intval($this->params['linkNum']);
        $curPage = intval($this->params['curPage']);
        $pageHtml = '';

        if ($totalNum > $pageSize) {
            $totalPage = @ceil($totalNum / $pageSize);
            if ($totalPage < $linkNum) {
                $from = 1;
                $to = $totalPage;
            } else {
                $from = $curPage - $this->params['offset'];
                $to = $from + $linkNum;
                if ($from < 1) {
                    $from = 1;
                    $to = $from + $linkNum - 1;
                } elseif ($to > $totalPage) {
                    $to = $totalPage;
                    $from = $totalPage - $linkNum + 1;
                }
            }
            if ($curPage > 1) {
                $pageHtml .= sprintf($this->templates['prev_page'], $this->makeUrl($curPage - 1));
            } else {
                $pageHtml .= $this->templates['prev_page_disabled'];
            }
            if ($curPage > $linkNum) $pageHtml .= sprintf($this->templates['page_item'], $this->makeUrl(1), '1...');
            for ($i = $from; $i <= $to; $i++) {
                if ($i == $curPage) {
                    $pageHtml .= sprintf($this->templates['page_item_active'], $i);
                } else {
                    $pageHtml .= sprintf($this->templates['page_item'], $this->makeUrl($i), $i);
                }
            }
            if ($totalPage > $to) $pageHtml .= sprintf($this->templates['page_item'], $this->makeUrl($totalPage), '...' . $totalPage);
            if ($curPage < $totalPage) {
                $pageHtml .= sprintf($this->templates['next_page'], $this->makeUrl($curPage + 1));
            } else {
                $pageHtml .= sprintf($this->templates['next_page_disabled']);
            }
            $pageHtml = sprintf($this->templates['wrapper'], $pageHtml);
        }

        return $pageHtml;
    }

    public function __toString()
    {
        return $this->makeHtml();
    }

}
