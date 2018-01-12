<?php

namespace Core\Lib;

/**
 * 加密/解密
 *
 * 只支持大部分 openssl_get_cipher_methods() 列出的加密方式，并不支持所有。
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Lib
 */
class Cipher
{

    const AES_128_CBC = 'AES-128-CBC';
    const AES_256_CBC = 'AES-256-CBC';

    // 向量长度
    private $ivLen;

    // 加密算法类型
    private $method;

    // 加密模式
    public $mode;

    // 是否对密文加上签名
    public $sign = true;

    /**
     * @param string $method 加密方式
     * @param bool $sign 是否加上签名
     */
    public function __construct($method = self::AES_256_CBC, $sign = true)
    {
        $this->method = $method;
        $this->ivLen = openssl_cipher_iv_length($method);
        $this->sign = $sign;
    }

    /**
     * 创建用于简单文本加密的Cipher对象
     *
     * 适用于加密cookies、简短文本
     *
     * @return Cipher
     */
    public static function createSimple()
    {
        return new self(self::AES_128_CBC, false);
    }

    /**
     * 加密
     *
     * @param string $text 要加密的内容
     * @param string $key 密钥
     * @param bool $raw 是否返回原始数据，true则返回原始二进制格式，false则返回base64编码后的字符串
     * @return string
     */
    public function encrypt($text, $key, $raw = false)
    {
        $iv = openssl_random_pseudo_bytes($this->ivLen);
        $cipherText = openssl_encrypt($text, $this->method, $key, OPENSSL_RAW_DATA, $iv);
        $result = $iv . $cipherText;
        if ($this->sign) {
            $sign = hash_hmac('sha256', $result, $key, true);
            $result = $sign . $result;
        }
        if (!$raw) {
            $result = base64_encode($result);
        }
        return $result;
    }


    /**
     * 解密
     *
     * @param string $text 密文
     * @param string $key 密钥
     * @param bool $raw 密文是否为原始二进制格式
     * @return string
     */
    public function decrypt($text, $key, $raw = false)
    {
        if (!$raw) {
            $text = base64_decode($text);
        }
        if ($this->sign) {
            $sign = substr($text, 0, 32);
            $text = substr($text, 32);
            $sign2 = hash_hmac('sha256', $text, $key, true);
            if (!$this->hashEquals($sign, $sign2)) {
                return false;
            }
        }
        $iv = substr($text, 0, $this->ivLen);
        $cipherRaw = substr($text, $this->ivLen);
        return openssl_decrypt($cipherRaw, $this->method, $key, OPENSSL_RAW_DATA, $iv);
    }

    /**
     * hash检查
     *
     * @param string $str1
     * @param string $str2
     * @return bool
     */
    private function hashEquals($str1 , $str2)
    {
        if (function_exists('hash_equals')) {
            return hash_equals($str1, $str2);
        }
        return substr_count($str1 ^ $str2, "\0") * 2 === strlen($str1 . $str2);
    }

}
