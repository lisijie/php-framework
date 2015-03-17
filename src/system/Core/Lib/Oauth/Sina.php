<?php

class OAuth_Sina extends OAuth {
	
	public function accessTokenUrl() {
		return 'https://api.weibo.com/oauth2/access_token';
	}
	
	public function authorizeUrl() {
		return 'https://api.weibo.com/oauth2/authorize';
	}
	
	public function apiUrl() {
		return 'https://api.weibo.com/2/';
	}
	
	/**
     * 发起一个API请求
     * @param $command 接口名称 如
     * @param $params 接口参数
     * @param $method 请求方式 POST|GET
     * @param $multi 图片信息
     * @return string
     */
	public function api($command, $params = array(), $method = 'POST') {
		//鉴权参数
		$params['access_token'] = $this->_accessToken;
		$params['source'] = $this->_clientId;
		
		$url = $this->apiUrl().trim($command, '/');
		
		$result = $this->httpRequest($url, $params, $method);
		$result = preg_replace('/[^\x20-\xff]*/', "", $result); //清除不可见字符
		$result = iconv("utf-8", "utf-8//ignore", $result); //UTF-8转码
		$result = json_decode($result, true);
		
		if ($this->_debug) {
			echo '<pre>';
			echo "请求接口: {$command}<br />";
			echo "请求参数：<br />";
			print_r($params);
			echo "返回结果：<br />";
			echo print_r($result);
			echo '</pre>';
		}
		
		return $result;
	}
}