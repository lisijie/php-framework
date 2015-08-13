<?php
namespace Core\Lib;

require dirname(__FILE__) . '/Amf/AMFBaseSerializer.php';
require dirname(__FILE__) . '/Amf/AMFDeserializer.php';
require dirname(__FILE__) . '/Amf/AMFObject.php';
require dirname(__FILE__) . '/Amf/AMFSerializer.php';
require dirname(__FILE__) . '/Amf/CharsetHandler.php';
require dirname(__FILE__) . '/Amf/Message.php';

/**
 * AMF编码/解码
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Lib
 */
class Amf
{

    // 编码
    public static function encode($data)
    {
        $ser = new \AMFSerializer();
        $ser->outBuffer = '';
        $ser->writeAmf3Data($data);
        return $ser->outBuffer;
    }

    // 解码
    public static function decode($raw)
    {
        $amf = new \AMFObject($raw);
        $des = new \AMFDeserializer($raw);
        return $des->readAmf3Data();
    }
}
