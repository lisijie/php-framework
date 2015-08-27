<?php

namespace Core;

use App;
use Core\Lib\Amf;

class AmfController extends Controller
{

    protected function message($message, $msgno = MSG_ERR, $redirect = NULL, $template = '')
    {
        $data = array(
            'ret' => $msgno,
            'msg' => $message,
            'time' => time(),
        );
        if ($redirect) $data['redirect'] = $redirect;
        $this->response->headers()->set('content-type', 'application/x-amf; charset=' . CHARSET);
        $this->response->setContent(Amf::encode($data));
        return $this->response;
    }

    protected function display($filename = '')
    {
        $data = array(
            'ret' => MSG_NONE,
            'data' => App::view()->getData(),
            'time' => time(),
        );
        $this->response->headers()->set('content-type', 'application/x-amf; charset=' . CHARSET);
        $this->response->setContent(Amf::encode($data));
        return $this->response;
    }

}