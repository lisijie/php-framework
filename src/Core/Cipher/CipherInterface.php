<?php
namespace Core\Cipher;

/**
 * 加密/解密接口
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Cipher
 */
interface CipherInterface
{
    /**
     * 加密
     *
     * @param string $text 要加密的内容
     * @param string $key 密钥
     * @param bool $raw 是否返回原始数据，true则返回原始二进制格式，false则返回base64编码后的字符串
     * @return string
     */
    public function encrypt($text, $key, $raw = false);

    /**
     * 解密
     *
     * @param string $text 密文
     * @param string $key 密钥
     * @param bool $raw 密文是否为原始二进制格式
     * @return string
     */
    public function decrypt($text, $key, $raw = false);

}