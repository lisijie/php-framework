<?php

use Core\Lib\HttpClient;

class HttpClientTest extends \Core\TestCase
{
	private $testUrl = 'http://localhost/php-framework/public/?r=api/test';

	public function testGet()
	{
		$request = new HttpClient('http://httpbin.org/get', HttpClient::HTTP_GET);
		$request->setParams([
			'foo' => 'bar',
		]);
		$result = $request->getJsonBody();
		$this->assertTrue($result['args']['foo'] == 'bar');
	}

	public function testSimplePost()
	{
		$request = new HttpClient('http://httpbin.org/post', HttpClient::HTTP_POST);
		$postData = ['username'=>'test'];
		$request->setParams($postData);
		$result = $request->getJsonBody();
		$this->assertTrue($result['form']['username'] == 'test');
	}

	public function testRawPost()
	{
		$request = new HttpClient($this->testUrl, HttpClient::HTTP_POST);
		$content = 'hello world';
		$tmpFile = tmpfile();
		fwrite($tmpFile, $content);
		$request->setBody($tmpFile);
		$result = $request->getJsonBody();
		$this->assertTrue($result['data']['raw'] == $content);
	}

	public function testUploadFile()
	{
		$tmpFile = tempnam(sys_get_temp_dir(), 'test_');
		$content = 'hello world';
		file_put_contents($tmpFile, $content);
		$request = new HttpClient($this->testUrl, HttpClient::HTTP_POST);
		$request->setFile('file', $tmpFile);
		$result = $request->getJsonBody()['data'];
		$this->assertTrue(isset($result['files']['file']));
		$this->assertSame($result['files']['file']['name'], basename($tmpFile));
		$this->assertSame($result['files']['file']['size'], strlen($content));
		unlink($tmpFile);
	}

	public function testCookie()
	{
		$request = new HttpClient('http://httpbin.org/cookies/set?k2=v2&k1=v1');
		$cookies = $request->getCookies();
		$this->assertTrue((isset($cookies['k1']) && $cookies['k1'] == 'v1'));
		$this->assertTrue((isset($cookies['k2']) && $cookies['k2'] == 'v2'));

		$request = new HttpClient('http://httpbin.org/cookies');
		$request->setCookie('foo', 'bar');
		$result = $request->getJsonBody();
		$this->assertTrue($result['cookies']['foo'] == 'bar');
	}

	public function testCookieJar()
	{
		$tmpFile = tempnam(sys_get_temp_dir(), 'test_');
		$request = new HttpClient('http://httpbin.org/cookies/set?k2=v2&k1=v1');
		$request->setCookieJar($tmpFile);
		$request->getResponse();
		$request->reset();
		$request->setUrl('http://httpbin.org/cookies');
		$cookies = $request->getJsonBody()['cookies'];
		$this->assertArrayHasKey('k1', $cookies);
		$this->assertArrayHasKey('k2', $cookies);
		unlink($tmpFile);
	}
}