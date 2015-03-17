<?php
/**
 * AMF编码/解码
 * 
 * @copyright (c) 2013 www.lisijie.org
 * @author lisijie <lsj86@qq.com>
 * @version $Id: Amf.php 1 2014-04-30 05:53:30Z lisijie $
*/

namespace Core\Lib;

require dirname(__FILE__).'/Amf/AMFBaseSerializer.php';
require dirname(__FILE__).'/Amf/AMFDeserializer.php';
require dirname(__FILE__).'/Amf/AMFObject.php';
require dirname(__FILE__).'/Amf/AMFSerializer.php';
require dirname(__FILE__).'/Amf/CharsetHandler.php';
require dirname(__FILE__).'/Amf/Message.php';

class Amf {

	public static function encode($data) {
		$ser = new \AMFSerializer();
		$ser->outBuffer='';
		$ser->writeAmf3Data($data);
		return $ser->outBuffer;
	}

	//解码
	public static function decode($raw) {
		$amf = new \AMFObject($raw);
		$des = new \AMFDeserializer($raw);
		return $des->readAmf3Data();
	}
}
