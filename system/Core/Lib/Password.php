<?php

namespace Core\Lib;

class Password
{
    public static function hash($password, $salt = '')
    {
        return md5(md5($password . $salt) . $salt);
    }
}
