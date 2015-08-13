<?php
namespace Core\Lib;

/**
 * 验证类
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Lib
 */
class Validate
{

    static $rules = array(
        'email' => '/^[\w\-\.]+@[\w\-\.]+(\.\w+)+$/',
        'telephone' => '/^(86)?(\d{3,4}-)?(\d{7,8})$/',
        'mobile' => '/^1\d{10}$/',
        'zipcode' => '/^[1-9]\d{5}$/',
        'qq' => '/^[1-9]\d{4,}$/',
        'date' => '/^(\d{4})(-|\/)(\d{1,2})\2(\d{1,2})$/',
        'datetime' => '/^(\d{4})(-|\/)(\d{1,2})\2(\d{1,2})\s(\d{1,2}):(\d{1,2}):(\d{1,2})$/',
        'chinese' => '/^[\u4e00-\u9fa5]+$/',
        'english' => '/^[A-Za-z]+$/',
        'varname' => '/^[a-zA-Z][\w]{0,254}$/', //变量名,函数名,控制器名等
        'integer' => '/^[\d]+$/', //整数验证
    );

    /**
     * 验证
     *
     * @param string $rule 验证规则，可以是现有规则名称，或者正则表达式
     * @param string $string 待验证的字符串
     * @return bool
     */
    public static function valid($rule, $string)
    {
        if (method_exists(__CLASS__, $rule)) {
            return call_user_func(array(__CLASS__, $rule), $string);
        } elseif (isset(self::$rules[$rule])) {
            return strlen($string) == 0 || preg_match(self::$rules[$rule], (string)$string);
        } else {
            return strlen($string) == 0 || preg_match($rule, (string)$string);
        }
    }

    /**
     * 是否合法的用户名
     *
     * @param string $string
     * @return boolean
     */
    public static function username($string)
    {
        if (empty($string)) return FALSE;
        $badchars = array("\\", '&', ' ', "'", '"', '/', '*', ',', '<', '>', "\r", "\t", "\n", '#', '$', '(', ')', '%', '@', '+', '?', ';', '^');
        foreach ($badchars as $char) {
            if (strpos($string, $char) !== false) {
                return false;
            }
        }
        return true;
    }

    /**
     * 验证URL是否合法
     *
     * @param $string
     * @return mixed
     */
    public static function url($string)
    {
        return filter_var($string, FILTER_VALIDATE_URL);
    }

    /**
     * 验证EMAIL是否合法
     *
     * @param $string
     * @return mixed
     */
    public static function email($string)
    {
        return filter_var($string, FILTER_VALIDATE_EMAIL);
    }

    /**
     * 验证IP地址是否合法
     *
     * @param $string
     * @return mixed
     */
    public static function ip($string)
    {
        return filter_var($string, FILTER_VALIDATE_IP);
    }
}
