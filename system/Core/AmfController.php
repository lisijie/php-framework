<?php

namespace Core;

use Core\Lib\Amf;
use Core\Http\Request;
use Core\Http\Response;

class AmfController extends Controller
{

    public function __construct(Request $request, Response $response)
    {
        parent::__construct($request, $response);
        $this->response->headers()->set('content-type', 'application/x-amf; charset=' . CHARSET);
    }

    protected function message($message, $msgno = MSG_ERR, $redirect = NULL, $template = '')
    {
        $data = array(
            'ret' => $msgno,
            'msg' => $message,
            'server_time' => time(),
        );
        if ($redirect) $data['redirect'] = $redirect;
        $this->response->setContent(Amf::encode($data));
        return $this->response;
    }

    protected function display($filename = '')
    {
        $data = array(
            'ret' => MSG_NONE,
            'data' => $this->view->getData(),
            'server_time' => time(),
        );
        $this->response->setContent(Amf::encode($data));
    }
}