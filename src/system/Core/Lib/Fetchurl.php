<?php
/**
 * URL抓取
 * 
 * @copyright (c) 2008-2010 JBlog (www.lisijie.org)
 * @author lisijie <lisijie86@gmail.com>
 * @version $Id: Fetchurl.php 1 2014-04-30 05:53:30Z lisijie $
*/

namespace Core\Lib;

class FetchUrl {
	
	private $_method;
	private $_data;
	private $_errno;
	private $_errmsg;
	
	public function __construct() {
		$this->_method = 'GET';
		$this->_errno = 0;
	}
	
	public function setMethod($method) {
		$method = strtoupper($method) == 'POST' ? 'POST' : 'GET';
		$this->_method = $method;
	}
	
	public function setPostData($data) {
		$this->_data = $data;
	}

	public function fetch($url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 3);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		if ($this->_method == 'POST') {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $this->_data);
		}
		if (substr($url,0,5) == 'https') {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		}
		$result = curl_exec($ch);
		if (false === $result) {
			$this->_errno = curl_errno($ch);
			$this->_errmsg = curl_error($ch);
		}
		curl_close($ch);
		return $result;
	}
	
	/**
	 * 抓取并另存为
	 * @param string $url
	 * @param string $path
	 */
	public function fetchTo($url, $path) {
		$result = $this->fetch($url);
		if (false === $result) return false;
		file_put_contents($path, $result);
		return true;
	}
	
	public function errno() {
		return $this->_errno;
	}
	
	public function errmsg() {
		return $this->_errmsg;
	}
}
