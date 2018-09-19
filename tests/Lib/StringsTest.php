<?php
use Core\Lib\Strings;

class StringsTest extends TestCase
{
    public function testBase64EncodeURL()
    {
        $src = 'hello，中国';
        $this->assertEquals(Strings::base64DecodeURL(Strings::base64EncodeURL($src)), $src);
    }
}