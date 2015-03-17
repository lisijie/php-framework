<?php

namespace Core\Controller;

use Core\Controller;
use Core\Lib\Amf;
use \App;

class AmfController extends Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->response->setHeader('content-type', 'application/x-amf; charset=' . CHARSET);
    }

    protected function message($message, $msgno = MSG_ERR, $redirect = NULL, $template = '')
    {
        $data = array(
            'ret' => $msgno,
            'msg' => $message,
            'server_time' => time(),
        );
        if ($redirect) $data['redirect'] = $redirect;
        $this->response->setBody(Amf::encode($data));
        App::terminate();
    }

    protected function display($filename = '')
    {
        $data = array(
            'ret' => MSG_NONE,
            'data' => $this->view->getData(),
            'server_time' => time(),
        );
        $this->response->setBody(Amf::encode($data));
    }
}