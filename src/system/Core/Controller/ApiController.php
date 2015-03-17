<?php

namespace Core\Controller;

use Core\Controller;
use \App;

class ApiController extends Controller
{

    protected $jsonpEnabled = true;
    protected $jsonpCallback = 'jsoncallback';

    public function __construct()
    {
        parent::__construct();
        $this->response->setHeader('content-type', 'application/json; charset=' . CHARSET);
    }

    protected function message($message, $msgno = MSG_ERR, $redirect = NULL, $template = '')
    {
        $data = array(
            'ret' => $msgno,
            'msg' => $message,
            'data' => $this->view->getData(),
        );
        if ($redirect) $data['redirect'] = $redirect;
        $this->response->setBody($this->encode($data));
        App::terminate();
    }

    protected function display($filename = '')
    {
        $data = array(
            'ret' => MSG_NONE,
            'data' => $this->view->getData()
        );
        $this->response->setBody($this->encode($data));
    }

    protected function encode($data)
    {
        $result = json_encode($data);
        $result = preg_replace_callback('#\\\u([0-9a-f]{4})#i', function($arr) {
            return iconv('UCS-2BE', 'UTF-8', pack('H4', $arr[1]));
        }, $result);
        $jsonpCallback = $this->get($this->jsonpCallback);
        if ($this->jsonpEnabled && $jsonpCallback != '') {
            $func = $jsonpCallback{0} == '?' ? '' : $jsonpCallback;
            return "{$func}($result)";
        } else {
            return $result;
        }
    }

}
