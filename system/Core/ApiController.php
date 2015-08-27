<?php

namespace Core;

use App;

class ApiController extends Controller
{

    protected $jsonpEnabled = true;
    protected $jsonpCallback = 'jsoncallback';

    protected function message($message, $msgno = MSG_ERR, $redirect = NULL, $template = '')
    {
        $data = array(
            'ret' => $msgno,
            'msg' => $message,
            'data' => App::view()->getData(),
        );
        if ($redirect) $data['redirect'] = $redirect;
        $this->response->headers()->set('content-type', 'application/json; charset=' . CHARSET);
        $this->response->setContent($this->jsonEncode($data));
        return $this->response;
    }

    protected function display($filename = '')
    {
        $data = array(
            'ret' => MSG_NONE,
            'data' => App::view()->getData()
        );
        $this->response->headers()->set('content-type', 'application/json; charset=' . CHARSET);
        $this->response->setContent($this->jsonEncode($data));
        return $this->response;
    }

    protected function jsonEncode($data)
    {
        $result = json_encode($data);
        $result = preg_replace_callback('#\\\u([0-9a-f]{4})#i', function ($arr) {
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
