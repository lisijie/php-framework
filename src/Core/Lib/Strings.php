<?php
namespace Core\Lib;

/**
 * 字符串助手类
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Lib
 */
class Strings
{

    /**
     * 生成一个token
     *
     * @return string
     */
    public static function makeToken()
    {
        return md5(uniqid(microtime(true), true));
    }

    /**
     * 产生随机字符串
     *
     * @param int $length 长度
     * @param string $string 包含的字符列表，必须是ASCII字符
     * @return string
     */
    public static function random($length, $string = '23456789ABCDEFGHIJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz')
    {
        return substr(str_shuffle(str_repeat($string, $length < strlen($string) ? 1 : ceil($length / strlen($string)))), 0, $length);
    }

    /**
     * 字符串截取
     *
     * @param string $str
     * @param int $len
     * @param string $dot
     * @param string $encoding
     * @return string
     */
    public static function truncate($str, $len = 0, $dot = '...', $encoding = CHARSET)
    {
        if (!$len || strlen($str) <= $len) return $str;
        $tempStr = '';
        $pre = $end = chr(1);
        $str = str_replace(['&amp;', '&quot;', '&lt;', '&gt;'], [$pre . '&' . $end, $pre . '"' . $end, $pre . '<' . $end, $pre . '>' . $end], $str);
        $encoding = strtolower($encoding);
        if ($encoding == 'utf-8') {
            $n = $tn = $noc = 0;
            while ($n < strlen($str)) {
                $t = ord($str[$n]);
                if ($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
                    $tn = 1;
                    $n++;
                    $noc++;
                } elseif (194 <= $t && $t <= 223) {
                    $tn = 2;
                    $n += 2;
                    $noc += 2;
                } elseif (224 <= $t && $t < 239) {
                    $tn = 3;
                    $n += 3;
                    $noc += 2;
                } elseif (240 <= $t && $t <= 247) {
                    $tn = 4;
                    $n += 4;
                    $noc += 2;
                } elseif (248 <= $t && $t <= 251) {
                    $tn = 5;
                    $n += 5;
                    $noc += 2;
                } elseif ($t == 252 || $t == 253) {
                    $tn = 6;
                    $n += 6;
                    $noc += 2;
                } else {
                    $n++;
                }
                if ($noc >= $len) {
                    break;
                }
            }
            if ($noc > $len) {
                $n -= $tn;
            }
            $tempStr = substr($str, 0, $n);
        } elseif ($encoding == 'gbk') {
            for ($i = 0; $i < $len; $i++) {
                $tempStr .= ord($str{$i}) > 127 ? $str{$i} . $str{++$i} : $str{$i};
            }
        }
        $tempStr = str_replace([$pre . '&' . $end, $pre . '"' . $end, $pre . '<' . $end, $pre . '>' . $end], ['&amp;', '&quot;', '&lt;', '&gt;'], $tempStr);
        return $tempStr . $dot;
    }

    /**
     * 字符串过滤
     *
     * @param string $string
     * @param boolean $isurl
     * @return string
     */
    public static function safeStr($string, $isurl = false)
    {
        $string = preg_replace('/[\\x00-\\x08\\x0B\\x0C\\x0E-\\x1F]/', '', $string);
        $string = str_replace(["\0", "%00", "\r"], '', $string);
        empty($isurl) && $string = preg_replace("/&(?!(#[0-9]+|[a-z]+);)/si", '&amp;', $string);
        $string = str_replace(["%3C", '<'], '&lt;', $string);
        $string = str_replace(["%3E", '>'], '&gt;', $string);
        $string = str_replace(['"', "'", "\t", '  '], ['&quot;', '&#39;', '    ', '&nbsp;&nbsp;'], $string);
        return trim($string);
    }

    /**
     * 数组元素串接
     *
     * 将数组的每个元素用逗号连接起来，并给每个元素添加单引号，用于SQL语句中串接ID
     * @param array $array
     * @return string
     */
    public static function simplode($array)
    {
        return "'" . implode("','", $array) . "'";
    }

    /**
     * 为特殊字符加上反斜杠
     *
     * 与addslashes不同之处在于本函数支持数组
     *
     * @param string|array $input
     * @return string|array 返回处理后的变量
     */
    public static function addSlashes($input)
    {
        if (!is_array($input)) return addslashes($input);
        foreach ($input as $key => $val) {
            $input[$key] = static::addSlashes($val);
        }
        return $input;
    }

    /**
     * 去除特殊字符的反斜杠
     *
     * 与stripslashes不同之处在于本函数支持数组
     *
     * @param string|array $input
     * @return string|array 返回处理后的变量
     */
    public static function delSlashes($input)
    {
        if (!is_array($input)) return stripslashes($input);
        foreach ($input as $key => $val) {
            $input[$key] = static::delSlashes($val);
        }
        return $input;
    }

    /**
     * 计算字符串长度，一个汉字为1
     *
     * @param string $string
     * @return int
     */
    public static function len($string)
    {
        return mb_strlen($string, CHARSET);
    }

    /**
     * base64编码为可用于URL参数形式
     *
     * @param string $string
     * @return string
     */
    public static function base64EncodeURL($string)
    {
        return str_replace(['+', '/'], ['-', '_'], rtrim(base64_encode($string), '='));
    }

    /**
     * 解码URL形式base64
     *
     * @param string $string
     * @return string
     */
    public static function base64DecodeURL($string)
    {
        return base64_decode(str_replace(['-','_'], ['+', '/'], $string));
    }

    /**
     * 检查字符串编码是否是UTF8
     *
     * @param string $value
     * @return bool
     */
    public static function isUTF8($value)
    {
        return $value === '' || preg_match('/^./su', $value) === 1;
    }
}
