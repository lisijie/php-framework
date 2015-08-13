<?php

namespace Core\Lib;

/**
 * 加密/解密
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Lib
 */
class Cipher
{

    // 加密算法类型
    public $algo;

    // 加密模式
    public $mode;

    // 是否对密文加上签名
    public $sign = true;

    // 哈希算法
    public $hashAlgo = 'md5';

    // 哈希16进制字符串长度
    public $hashLen = 32;

    public function __construct($algo = MCRYPT_RIJNDAEL_256, $mode = MCRYPT_MODE_CBC, $sign = true)
    {
        $this->algo = $algo;
        $this->mode = $mode;
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
        return new self(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB, false);
    }

    /**
     * 设置哈希算法
     *
     * @param string $algo 算法名
     * @param int $len 哈希16进制字符串长度
     */
    public function setHashAlgo($algo, $len)
    {
        $this->hashAlgo = $algo;
        $this->hashLen = $len;
    }

    /**
     * 加密
     *
     * @param string $text 要加密的内容
     * @param string $key 密钥
     * @param bool $raw 是否返回原始数据，true则返回原始二进制格式，false则返回16进制字符串
     * @return string
     */
    public function encrypt($text, $key, $raw = false)
    {
        $iv = '';
        if ($this->mode != MCRYPT_MODE_ECB) { // ECB模式不需要创建向量
            $iv = mcrypt_create_iv(mcrypt_get_iv_size($this->algo, $this->mode), MCRYPT_RAND);
        }
        $key  = hash($this->hashAlgo, $key, true);
        $text = mcrypt_encrypt($this->algo, $key, $text, $this->mode, $iv) . $iv;
        if (!$raw) {
            $text = unpack('H*0', $text);
            $text = $text[0];
        }
        if ($this->sign) {
            $text = hash($this->hashAlgo, $key . $text) . $text;
        }

        return $text;
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
        $key = hash($this->hashAlgo, $key, true);
        if ($this->sign) {
            $hash = substr($text, 0, $this->hashLen);
            $text = substr($text, $this->hashLen);
            if ($hash != hash($this->hashAlgo, $key . $text)) { // 哈希校验
                return '';
            }
            if (!$raw) {
                $text = pack('H*', $text);
            }
        } elseif (!$raw) {
            $text = pack('H*', $text);
        }
        $iv = '';
        if ($this->mode != MCRYPT_MODE_ECB) {
            $iv = substr($text, -mcrypt_get_iv_size($this->algo, $this->mode));
            $text = substr($text, 0, -strlen($iv));
        }

        return rtrim(mcrypt_decrypt($this->algo, $key, $text, $this->mode, $iv), "\x0");
    }
}
