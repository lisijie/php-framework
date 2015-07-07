<?php

namespace Core;

use \App;
use Core\View\ViewAbstract;

/**
 * 控制器基类
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core
 */
class Controller
{
    /**
     * 请求对象
     *
     * @var \Core\Http\Request
     */
    protected $request;

    /**
     * 输出对象
     *
     * @var \Core\Http\Response
     */
    protected $response;

    /**
     * 日志对象
     *
     * @var \Core\Logger\LoggerInterface
     */
    protected $logger;

    /**
     * 视图模板
     *
     * @var \Core\View\ViewInterface
     */
    protected $view;

    /**
     * 构造方法，不可重写
     *
     * 子类可通过重写init()方法完成初始化
     */
    public function __construct()
    {
        $this->request  = App::request();
        $this->response = App::response();
        $this->logger   = App::logger();
        $this->view     = App::view();
    }

    /**
     * 模块子类初始化方法
     */
    public function init()
    {

    }

    /**
     * 动作执行后调用
     */
    public function after()
    {

    }

    /**
     * 获取服务器环境变量
     *
     * @param string $name 名称
     * @return string
     */
    protected function getServer($name)
    {
        return $this->request->getServer($name);
    }

    /**
     * 获取GET/POST值
     *
     * @param string $name
     * @return mixed
     */
    protected function get($name)
    {
        return $this->request->get($name);
    }

    /**
     * 添加一个输出变量
     *
     * @param mixed $name 变量
     * @param mixed $value 变量的值
     */
    protected function assign($name, $value = null)
    {
        $this->view->assign($name, $value);
    }

    /**
     * 获取请求来源地址
     *
     * @return string
     */
    protected function getRefer()
    {
        if ($this->getServer('HTTP_REFERER') == '' ||
            strpos($this->getServer('HTTP_REFERER'), $this->getServer('HTTP_HOST')) === FALSE
        ) {
            $refer = '';
        } else {
            $refer = $this->getServer('HTTP_REFERER');
            if (strpos($refer, '#') !== false) {
                $refer = substr($refer, 0, strpos($refer, '#'));
            }
        }

        return $refer;
    }

    /**
     * 提示消息
     *
     * @param string $message 提示消息
     * @param int $msgno 消息号
     * @param string $redirect 跳转地址
     * @param string $template 模板文件
     */
    protected function message($message, $msgno = MSG_ERR, $redirect = NULL, $template = 'message')
    {
        $data = array(
            'ret' => $msgno,
            'msg' => $message,
            'redirect' => $redirect,
        );
        $this->view->assign($data);
        $this->response->setBody($this->view->render($template));
        App::terminate();
    }

    /**
     * URL跳转
     *
     * @param string $url 目的地址
     */
    protected function redirect($url)
    {
        $this->response->redirect($url);
        App::terminate();
    }

    /**
     * 输出结果
     *
     * @param string $filename
     */
    protected function display($filename = '')
    {
        if (empty($filename)) {
            $filename = CUR_ROUTE;
        }
        
        $this->response->setBody($this->view->render($filename));
    }
}
