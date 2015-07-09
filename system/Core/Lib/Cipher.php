<?php

namespace Core\Lib;

/**
 * 加密/解密
 *
 * @author lisijie <lsj86@qq.com>
 * @package core
 */
class Cipher
{

    /**
     * 加密
     *
     * @param string $text 要加密的明文
     * @param string $key 密钥
     * @param string $algo 加密算法
     * @param string $mode 加密模式
     * @return string
     */
    public static function encrypt($text, $key, $algo = MCRYPT_RIJNDAEL_256, $mode = MCRYPT_MODE_CBC)
    {
        $iv = mcrypt_create_iv(mcrypt_get_iv_size($algo, $mode), MCRYPT_RAND);

        $text = mcrypt_encrypt($algo, hash('sha256', $key, TRUE), $text, $mode, $iv) . $iv;

        return hash('sha256', $key . $text) . $text;
    }


    /**
     * 解密
     *
     * @param string $text 密文
     * @param string $key 密钥
     * @param string $algo 加密算法
     * @param string $mode 加密模式
     * @return string
     */
    public static function decrypt($text, $key, $algo = MCRYPT_RIJNDAEL_256, $mode = MCRYPT_MODE_CBC)
    {
        $hash = substr($text, 0, 64);
        $text = substr($text, 64);

        if (hash('sha256', $key . $text) != $hash) return '';

        $iv = substr($text, -mcrypt_get_iv_size($algo, $mode));

        return rtrim(mcrypt_decrypt($algo, hash('sha256', $key, TRUE), substr($text, 0, -strlen($iv)), $mode, $iv), "\x0");
    }
}
