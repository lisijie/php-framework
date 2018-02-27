<?php

namespace Core;

use App;
use Core\Exception\AppException;
use Core\Http\Request;
use Core\Http\Response;

/**
 * 控制器基类
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core
 */
class Controller extends Component
{
    /**
     * 输出的数据
     * @var array
     */
    private $data = [];

    /**
     * 默认动作
     * @var string
     */
    protected $defaultAction = 'index';

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
     * 提示消息的模板文件
     *
     * @var string
     */
    protected $messageTemplate = 'message';

    /**
     * 构造方法，不可重写
     * 子类可通过重写init()方法完成初始化
     *
     * @param Request $request 请求对象
     * @param Response $response 输出对象
     */
    public final function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * 控制器初始化方法，执行初始化操作
     */
    public function init()
    {

    }

    /**
     * 动作执行前置方法
     *
     * 该方法会在Action方法被执行前调用，只有在本方法返回true时，才会执行接下来的Action方法，否则将返回403错误页面。
     * 可以用来做功能开关、访问控制等。
     */
    public function before()
    {
        return true;
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
    protected function getQuery($name = null, $default = null, $filter = true)
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
    protected function getPost($name = null, $default = null, $filter = true)
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
        if (is_array($name)) {
            $this->data = array_merge($this->data, $name);
        } else {
            $this->data[$name] = $value;
        }
    }

    /**
     * 返回要输出的数据
     *
     * @return array
     */
    protected function getData()
    {
        return $this->data;
    }

    /**
     * 设置视图的布局模板文件
     *
     * @param $filename
     */
    protected function setLayout($filename)
    {
        App::view()->setLayout($filename);
    }

    /**
     * 设置视图的子布局模板文件
     *
     * @param $name
     * @param $filename
     */
    protected function setLayoutSection($name, $filename)
    {
        App::view()->setLayoutSection($name, $filename);
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
     * URL跳转
     *
     * @param string $url 目的地址
     * @param int $status 状态码
     * @return Response 输出对象
     */
    protected function redirect($url, $status = 302)
    {
        return $this->response->redirect($url, $status);
    }

    /**
     * 跳转回首页
     *
     * @return Response
     */
    protected function goHome()
    {
        return $this->redirect($this->request->getBaseUrl() ?: '/');
    }

    /**
     * 跳转到来源页面
     *
     * 优先级：
     * 1. URL中的refer参数
     * 2. 存在名为refer的cookie
     * 3. 使用HTTP_REFERER
     *
     * @param string $defaultUrl 默认URL
     * @param bool $verifyHost 是否检查域名
     * @return Response
     */
    protected function goBack($defaultUrl = '', $verifyHost = true)
    {
        $url = $this->get('refer');
        if (empty($url)) {
            $url = $this->request->getCookie('refer');
        }
        if (empty($url)) {
            $url = $this->request->getReferrer();
        }
        if (empty($url)) {
            $url = $defaultUrl;
        }
        if (strpos($url, '//') === false) {
            $url = '/' . ltrim($url, '/');
        } elseif ($verifyHost) {
            $host = parse_url($url, PHP_URL_HOST);
            if (empty($host) || $host != $this->request->getHostName()) {
                $url = '';
            }
        }
        // 如果没有来源页面，跳转到首页
        if (empty($url)) {
            return $this->goHome();
        }
        return $this->redirect($url);
    }

    /**
     * 刷新当前页面
     *
     * @param string $anchor 附加url hash
     * @return mixed
     */
    protected function refresh($anchor = '')
    {
        return $this->redirect($this->request->getRequestUri() . $anchor);
    }

    /**
     * JSON编码
     *
     * @param $data
     * @return mixed|string
     */
    protected function jsonEncode($data)
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 输出JSON格式
     *
     * @param array $data 输出的数据，默认使用assign的数据
     * @return Response
     */
    protected function serveJSON(array $data = [])
    {
        if (empty($data)) {
            $data = $this->data;
        }
        $content = $this->jsonEncode($data);
        $charset = $this->response->getCharset();
        $this->response->setHeader('content-type', "application/json; charset={$charset}");
        $this->response->setContent($content);
        return $this->response;
    }

    /**
     * 提示消息
     *
     * @param string $message 提示消息
     * @param int $code 消息号
     * @param string $jumpUrl 跳转地址
     * @return Response 输出对象
     */
    public function message($message, $code = MSG_ERR, $jumpUrl = NULL)
    {
        $data = [
            'code' => $code,
            'msg' => $message,
            'jumpUrl' => $jumpUrl,
        ];
        $this->assign($data);
        $this->response->setContent(App::view()->render($this->messageTemplate, $this->data));
        return $this->response;
    }

    /**
     * 渲染模板并返回输出对象
     *
     * @param string $filename
     * @return Response 输出对象
     */
    public function display($filename = '')
    {
        if (empty($filename)) {
            $filename = CUR_ROUTE;
        }
        $this->response->setContent(App::view()->render($filename, $this->data));
        return $this->response;
    }

    /**
     * 渲染模板
     *
     * @param string $filename
     * @param array $data
     * @return string
     */
    public function render($filename, $data = [])
    {
        $data = array_merge($this->data, $data);
        return App::view()->render($filename, $data);
    }

    /**
     * 执行控制器方法
     *
     * @param string $actionName 方法名
     * @param array $params 参数列表
     * @return Response|mixed
     * @throws AppException
     */
    public function execute($actionName, $params = [])
    {
        if (empty($actionName)) {
            $actionName = $this->defaultAction;
        }
        $actionName .= 'Action';
        if (!method_exists($this, $actionName)) {
            throw new \BadMethodCallException("方法不存在: " . get_class($this) . "::{$actionName}");
        }

        $method = new \ReflectionMethod($this, $actionName);
        if (!$method->isPublic()) {
            throw new \BadMethodCallException("调用非公有方法: " . get_class($this) . "::{$actionName}");
        }

        $args = [];
        $methodParams = $method->getParameters();
        if (!empty($methodParams)) {
            foreach ($methodParams as $p) {
                $default = $p->isOptional() ? $p->getDefaultValue() : null;
                $value = array_key_exists($p->getName(), $params) ? $params[$p->getName()] : $default;
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
