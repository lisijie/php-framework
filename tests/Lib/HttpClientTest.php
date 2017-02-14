<?php

use Core\Lib\HttpClient;

class HttpClientTest extends TestCase
{
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
        $postData = ['username' => 'test'];
        $request->setParams($postData);
        $result = $request->getJsonBody();
        $this->assertTrue($result['form']['username'] == 'test');
    }

    public function testUploadFile()
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_');
        $content = 'hello world';
        file_put_contents($tmpFile, $content);
        $request = new HttpClient('http://httpbin.org/post', HttpClient::HTTP_POST);
        $request->setFile('file', $tmpFile);
        $result = $request->getJsonBody();
        $this->assertArrayHasKey('file', $result['files']);
        $this->assertEquals($content, $result['files']['file']);
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

    public function testBaseAuth()
    {
        $request = new HttpClient('http://httpbin.org/basic-auth/user/passwd');
        $request->setBasicAuth('user', 'passwd');
        $this->assertTrue($request->getResponseCode() == 200);
    }
}