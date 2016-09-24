<?php

namespace Core;

use App;

/**
 * JSON控制器
 *
 * 继承该控制器，所有调用 message() 和 display() 输出的内容都转为JSON，而不是渲染模板。
 * 用于开发JSON API。输出的格式如下：
 *  - code 错误码，0表示没有错误发生。
 *  - msg  错误消息，当 code > 0 时有效。
 *  - data 接口返回的数据。
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core
 */
class JsonController extends Controller
{

	protected $jsonpEnabled = true;
	protected $jsonpCallback = 'jsoncallback';

	protected function message($message, $msgno = MSG_ERR, $redirect = NULL, $template = '')
	{
		$data = [
			'code' => $msgno,
			'msg' => $message,
			'data' => $this->getData(),
		];
		if ($redirect) $data['redirect'] = $redirect;
		$charset = $this->response->getCharset();
		$this->response->headers()->set('content-type', "application/json; charset={$charset}");
		$this->response->setContent($this->jsonEncode($data));
		return $this->response;
	}

	protected function display($filename = '')
	{
		$data = [
			'code' => MSG_NONE,
			'data' => $this->getData()
		];
		$charset = $this->response->getCharset();
		$this->response->headers()->set('content-type', "application/json; charset={$charset}");

		$json = $this->jsonEncode($data);
		$jsonpCallback = $this->get($this->jsonpCallback);
		if ($this->jsonpEnabled && $jsonpCallback != '') {
			$func = $jsonpCallback{0} == '?' ? '' : $jsonpCallback;
			$this->response->setContent("{$func}($json)");
		} else {
			$this->response->setContent($json);
		}
		return $this->response;
	}
}
