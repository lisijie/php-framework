<?php

class OAuth_Tencent extends OAuth {
	
	public function accessTokenUrl() {
		return 'https://open.t.qq.com/cgi-bin/oauth2/access_token';
	}
	
	public function authorizeUrl() {
		return 'https://open.t.qq.com/cgi-bin/oauth2/authorize';
	}
	
	public function apiUrl() {
		return 'https://open.t.qq.com/api/';
	}
	
	/**
     * 发起一个腾讯API请求
     * @param $command 接口名称 如：t/add
     * @param $params 接口参数，注意所有API必须带上openid参数;
     * @param $method 请求方式 POST|GET
     * @param $multi 图片信息
     * @return string
     */
	public function api($command, $params = array(), $method = 'GET') {
		//鉴权参数
		$params['access_token'] = $this->_accessToken;
		$params['oauth_consumer_key'] = $this->_clientId;
		$params['oauth_version'] = '2.a';
		$params['clientip'] = get_ip();
		$params['scope'] = 'all';
		$params['seqid'] = time();
		$params['serverip'] = $_SERVER['SERVER_ADDR'];
		$params['format'] = 'json';
		
		$url = $this->apiUrl().trim($command, '/');
		
		$result = $this->httpRequest($url, $params);
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