<?php
use Core\Http\Uri;

class UriTest extends TestCase
{
    public function testUri()
    {
        $uri = new Uri('https://user:pass@localhost:81/a b c/b?c=' . urlencode('中 国') . '#a1');
        $this->assertEquals($uri->getScheme(), 'https');
        $this->assertEquals($uri->getAuthority(), 'user:pass@localhost:81');
        $this->assertEquals($uri->getHost(), 'localhost');
        $this->assertEquals($uri->getPort(), 81);
        $this->assertEquals($uri->getUserInfo(), 'user:pass');
        $this->assertEquals($uri->getQuery(), 'c=' . urlencode('中 国'));
        $this->assertEquals(rawurldecode($uri->getPath()), '/a b c/b');

    }
}