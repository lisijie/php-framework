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
        $tempstr = '';
        $pre = $end = chr(1);
        $str = str_replace(array('&amp;', '&quot;', '&lt;', '&gt;'), array($pre . '&' . $end, $pre . '"' . $end, $pre . '<' . $end, $pre . '>' . $end), $str);
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
            $tempstr = substr($str, 0, $n);
        } elseif ($encoding == 'gbk') {
            for ($i = 0; $i < $len; $i++) {
                $tempstr .= ord($str{$i}) > 127 ? $str{$i} . $str{++$i} : $str{$i};
            }
        }
        $tempstr = str_replace(array($pre . '&' . $end, $pre . '"' . $end, $pre . '<' . $end, $pre . '>' . $end), array('&amp;', '&quot;', '&lt;', '&gt;'), $tempstr);
        return $tempstr . $dot;
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
        $string = str_replace(array("\0", "%00", "\r"), '', $string);
        empty($isurl) && $string = preg_replace("/&(?!(#[0-9]+|[a-z]+);)/si", '&amp;', $string);
        $string = str_replace(array("%3C", '<'), '&lt;', $string);
        $string = str_replace(array("%3E", '>'), '&gt;', $string);
        $string = str_replace(array('"', "'", "\t", '  '), array('&quot;', '&#39;', '    ', '&nbsp;&nbsp;'), $string);
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
     * @param string|array $string
     * @return string|array 返回处理后的变量
     */
    public static function addSlashes($string)
    {
        if (!is_array($string)) return addslashes($string);
        foreach ($string as $key => $val) {
            $string[$key] = static::addSlashes($val);
        }
        return $string;
    }

    /**
     * 去除特殊字符的反斜杠
     *
     * 与stripslashes不同之处在于本函数支持数组
     *
     * @param string|array $string
     * @return string|array 返回处理后的变量
     */
    public static function delSlashes($string)
    {
        if (!is_array($string)) return stripslashes($string);
        foreach ($string as $key => $val) {
            $string[$key] = static::delSlashes($val);
        }
        return $string;
    }

    /**
     * 计算字符串长度，一个汉字为1
     * @param string $string
     * @return int
     */
    public static function len($string)
    {
        return mb_strlen($string, CHARSET);
    }

    /**
     * XSS过滤
     *
     * @param string $string
     * @return string
     */
    public static function removeXss($string)
    {
        // remove all non-printable characters. CR(0a) and LF(0b) and TAB(9) are allowed
        // this prevents some character re-spacing such as <java\0script>
        // note that you have to handle splits with \n, \r, and \t later since they *are* allowed in some inputs
        $val = preg_replace('/([\x00-\x08,\x0b-\x0c,\x0e-\x19])/', '', $string);

        // straight replacements, the user should never need these since they're normal characters
        // this prevents like <IMG SRC=@avascript:alert('XSS')>
        $search = 'abcdefghijklmnopqrstuvwxyz';
        $search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $search .= '1234567890!@#$%^&*()';
        $search .= '~`";:?+/={}[]-_|\'\\';
        for ($i = 0; $i < strlen($search); $i++) {
            // ;? matches the ;, which is optional
            // 0{0,7} matches any padded zeros, which are optional and go up to 8 chars

            // @ @ search for the hex values
            $val = preg_replace('/(&#[xX]0{0,8}' . dechex(ord($search[$i])) . ';?)/i', $search[$i], $val); // with a ;
            // @ @ 0{0,7} matches '0' zero to seven times
            $val = preg_replace('/(&#0{0,8}' . ord($search[$i]) . ';?)/', $search[$i], $val); // with a ;
        }

        // now the only remaining whitespace attacks are \t, \n, and \r
        $ra1 = array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'style', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base');
        $ra2 = array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');
        $ra = array_merge($ra1, $ra2);

        $found = true; // keep replacing as long as the previous round replaced something
        while ($found == true) {
            $val_before = $val;
            for ($i = 0; $i < sizeof($ra); $i++) {
                $pattern = '/';
                for ($j = 0; $j < strlen($ra[$i]); $j++) {
                    if ($j > 0) {
                        $pattern .= '(';
                        $pattern .= '(&#[xX]0{0,8}([9ab]);)';
                        $pattern .= '|';
                        $pattern .= '|(&#0{0,8}([9|10|13]);)';
                        $pattern .= ')*';
                    }
                    $pattern .= $ra[$i][$j];
                }
                $pattern .= '/i';
                $replacement = substr($ra[$i], 0, 2) . '<x>' . substr($ra[$i], 2); // add in <> to nerf the tag
                $val = preg_replace($pattern, $replacement, $val); // filter out the hex tags
                if ($val_before == $val) {
                    // no replacements were made, so exit the loop
                    $found = false;
                }
            }
        }
        return $val;
    }

}
