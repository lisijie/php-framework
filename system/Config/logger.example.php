<?php
/**
 * 日志配置文件
 *
 * @author lisijie <lsj86@qq.com>
 * @package config
 */

return array(

    //默认日志
    'default' => array(
        //日志处理器1
        array(
            'level' => 5, //日志级别: 1-5
            'handler' => 'FileHandler', // 日志处理器
	        'formatter' => 'LineFormatter', // 格式器，默认LineFormatter
	        'date_format' => 'c', // 日期格式，默认ISO8601
            'config' => array(
                'savepath' => DATA_PATH . '/logs/', //日志保存目录
                'filesize' => 0, //文件分割大小
                'filename' => '{Y}{m}{d}.log',
            ),
        )
    ),
    // DB的日志处理，当DB开启debug时使用
    'database' => array(
        //日志处理器1
        array(
            'level' => 3, //日志级别: 1-5
            'handler' => 'FileHandler', //日志处理器
            'config' => array(
                'savepath' => DATA_PATH . '/logs/', //日志保存目录
                'filesize' => 0, //文件分割大小
                'filename' => '{Y}{m}{d}.log',
            ),
        )
    ),
);
