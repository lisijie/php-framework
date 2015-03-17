<?php

namespace Core\Lib;

abstract class OAuth
{
	
	protected $_clientId;
	protected $_clientSecret;
	protected $_accessToken;
	protected $_debug = false;
	
	private function __construct($clientId, $clientSecret, $accessToken = NULL)
	{
		$this->_clientId = $clientId;
		$this->_clientSecret = $clientSecret;
		$this->_accessToken = $accessToken;
	}
	
	abstract public function authorizeUrl();
	
	abstract public function accessTokenUrl();
	
	abstract public function apiUrl();
	
	abstract public function api($command, $params = array(), $method = 'GET');
	
	/**
	 * 开启/关闭调试模式
	 * 
	 * @param boolean $bool
	 */
	public function setDebug($bool = TRUE)
	{
		$this->_debug = $bool;
	}
	
	/**
	 * 设置 AccessToken
	 * @param string $token
	 */
	public function setAccessToken($token)
	{
		$this->_accessToken = $token;
	}
	
	/**
	 * 获取授权URL
	 * 
	 * @param string $url 授权后的回调地址
	 * @param string $response_type 支持的值包括 code 和token 默认值为code
	 */
	public function getAuthorizeUrl($url, $response_type = 'code')
	{
		$params = array();
		$params['client_id'] = $this->_clientId;
		$params['redirect_uri'] = $url;
		$params['response_type'] = $response_type;
		return $this->authorizeUrl() . "?" . http_build_query($params);
	}
	
	/**
     * 获取请求token的url
     * @param $code 调用authorize时返回的code
     * @param $redirect_uri 回调地址，必须和请求code时的redirect_uri一致
     * @return string
     */
	public function getAccessTokenUrl($code, $redirect_uri)
	{
		$params = array();
		$params['client_id'] = $this->_clientId;
		$params['client_secret'] = $this->_clientSecret;
		$params['grant_type'] = 'authorization_code';
		$params['code'] = $code;
		$params['redirect_uri'] = $redirect_uri;
		return $this->accessTokenUrl() . "?" . http_build_query($params);
	}
	
	/**
	 * 获取请求token
	 */
	public function getAccessToken($code, $redirect_uri)
	{
		$url = $this->getAccessTokenUrl($code, $redirect_uri);
		return $this->httpRequest($url);
	}
	
	protected function httpRequest($url, $params = array(), $method = 'POST', $headers = array())
	{
		if(!function_exists('curl_init')) exit('Need to open the curl extension');
        $method = strtoupper($method);
        $ci = curl_init();
        curl_setopt($ci, CURLOPT_USERAGENT, 'OAuth2.0');
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ci, CURLOPT_TIMEOUT, 3);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ci, CURLOPT_HEADER, false);
        $headers = (array)$headers;
        switch ($method) {
            case 'POST':
                curl_setopt($ci, CURLOPT_POST, TRUE);
                if (!empty($params)) {
                    if($multi) {
                        foreach($multi as $key => $file)
                        {
                            $params[$key] = '@' . $file;
                        }
                        curl_setopt($ci, CURLOPT_POSTFIELDS, $params);
                        $headers[] = 'Expect: ';
                    }
                    else
                    {
                        curl_setopt($ci, CURLOPT_POSTFIELDS, http_build_query($params));
                    }
                }
                break;
            case 'GET':
                if (!empty($params)) {
                    $url = $url . (strpos($url, '?') ? '&' : '?')
                        . (is_array($params) ? http_build_query($params) : $params);
                }
                break;
        }
        curl_setopt($ci, CURLINFO_HEADER_OUT, TRUE );
        curl_setopt($ci, CURLOPT_URL, $url);
        if($headers) {
            curl_setopt($ci, CURLOPT_HTTPHEADER, $headers );
        }
        $response = curl_exec($ci);
        curl_close ($ci);
        return $response;
	}
	
	public static function factory($platform, $clientId, $clientSecret, $accessToken = NULL)
	{
		$className = 'OAuth_'.ucfirst($platform);
		if (class_exists($className)) {
			return new $className($clientId, $clientSecret, $accessToken);
		}
		throw new CoreException("class {$className} not exists.");
	}
}
