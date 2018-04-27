<?php
namespace App\Util;

/**
 * 数组操作
 *
 * @author lisijie <lsj86@qq.com>
 * @package App\Util
 */
class Arrays
{
    /**
     * 使用指定字段重新索引数组
     *
     * @param array $data
     * @param $idx
     * @return array
     */
    public static function index(array $data, $idx)
    {
        if (empty($data) || !isset($data[0][$idx])) {
            return $data;
        }
        $result = [];
        foreach ($data as $row) {
            $result[$row[$idx]] = $row;
        }
        return $result;
    }
}