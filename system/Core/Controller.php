<?php

namespace Core;

use \App;
use Core\Http\Request;
use Core\Http\Response;
use Core\Exception\AppException;

/**
 * 控制器基类
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core
 */
class Controller
{

    /**
     * 默认动作
     * @var string
     */
    protected $defaultAction = 'indexAction';

    /**
     * 请求对象
     * @var \Core\Http\Request
     */
    protected $request;

    /**
     * 输出对象
     * @var \Core\Http\Response
     */
    protected $response;

    /**
     * 构造方法，不可重写
     *
     * 子类可通过重写init()方法完成初始化
     */
    public final function __construct(Request $request, Response $response)
    {
        $this->request  = $request;
        $this->response = $response;
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
     * @param mixed $default
     * @param bool $filter
     * @return mixed
     */
    protected function get($name, $default = null, $filter = true)
    {
        if (null === ($value = $this->request->getQuery($name, null, $filter))) {
            $value = $this->request->getPost($name, $default, $filter);
        }
        return $value;
    }

    /**
     * 获取GET值
     *
     * @param string $name
     * @param mixed $default
     * @param bool $filter
     * @return mixed|null
     */
    protected function getQuery($name, $default = null, $filter = true)
    {
        return $this->request->getQuery($name, $default, $filter);
    }

    /**
     * 获取POST值
     *
     * @param string $name
     * @param mixed $default
     * @param bool $filter
     * @return mixed|null
     */
    protected function getPost($name, $default = null, $filter = true)
    {
        return $this->request->getPost($name, $default, $filter);
    }

    /**
     * 添加一个输出变量
     *
     * @param mixed $name 变量
     * @param mixed $value 变量的值
     */
    protected function assign($name, $value = null)
    {
        App::view()->assign($name, $value);
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
     * @return Response 输出对象
     */
    protected function message($message, $msgno = MSG_ERR, $redirect = NULL, $template = 'message')
    {
        $data = array(
            'ret' => $msgno,
            'msg' => $message,
            'redirect' => $redirect,
        );
        $this->response->setContent(App::view()->render($template, $data));
        return $this->response;
    }

    /**
     * URL跳转
     *
     * @param string $url 目的地址
     * @return Response 输出对象
     */
    protected function redirect($url)
    {
        $this->response->redirect($url);
        return $this->response;
    }

    /**
     * 输出结果
     *
     * @param string $filename
     * @return Response 输出对象
     */
    protected function display($filename = '')
    {
        if (empty($filename)) {
            $filename = CUR_ROUTE;
        }
        
        $this->response->setContent(App::view()->render($filename));
        return $this->response;
    }

    protected function ajaxReturn($data, $format = 'json', $status = 200)
    {

    }

    /**
     * 执行当前控制器方法
     *
     * @param string $actionName 方法名
     * @param array $params 参数列表
     * @return Response|mixed
     * @throws AppException
     */
    public function runActionWithParams($actionName, $params = array())
    {
        if (empty($actionName)) {
            $actionName = $this->defaultAction;
        }
        if (!method_exists($this, $actionName)) {
            throw new \BadMethodCallException("方法不存在: {$actionName}");
        }

        $method = new \ReflectionMethod($this, $actionName);
        if (!$method->isPublic()) {
            throw new \BadMethodCallException("调用非公有方法: {$actionName}");
        }

        $args = array();
        $methodParams = $method->getParameters();
        if (!empty($methodParams)) {
            foreach ($methodParams as $p) {
                $default = $p->isOptional() ? $p->getDefaultValue() : null;
                $value = $this->request->get($p->getName(), $default);
                if (null === $value && !$p->isOptional()) {
                    throw new AppException('缺少请求参数:' . $p->getName());
                }
                $args[] = $value;
            }
        }
        $result = $method->invokeArgs($this, $args);
        if ($result instanceof Response) {
            return $result;
        } elseif (null !== $result) {
            $this->response->setContent(strval($result));
        }
        return $this->response;
    }
}
