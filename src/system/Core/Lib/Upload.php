<?php
/**
 * 文件上传类 
 * 
 * @copyright (c) 2008-2012 JBlog (www.lisijie.org)
 * @author lisijie <lisijie86@gmail.com>
 * @version $Id: Upload.php 173 2014-11-02 12:27:08Z lisijie $
*/

namespace Core\Lib;

class Upload
{

	private $savePath = 'upload/';
	private $allowTypes = array();
	private $maxsize = 0;
	
	public function __construct($options = array())
	{
        if (isset($options['allow_types'])) {
            if (!is_array($options['allow_types'])) {
                $options['allow_types'] = explode('|', $options['allow_types']);
            }
            $this->allowTypes = $options['allow_types'];
        }
        if (isset($options['save_path'])) {
            $this->savePath = $options['save_path'];
        }
        if (isset($options['maxsize'])) {
            $this->maxsize = intval($options['maxsize']);
        }
		$this->savePath = str_replace(array('{y}','{Y}','{m}','{d}','\\','..'), array(date('y'),date('Y'),date('m'),date('d'),'/', ''), $this->savePath);
		if (substr($this->savePath, -1) != '/') $this->savePath .= '/';
		$this->maxsize *= 1024; //最大允许上传的文件大小/byte
		$this->makepath($this->savePath); //创建目录
	}
	
	/**
	 * 返回本次上传的附件保存目录
	 */
	public function getSavePath()
	{
		return $this->savePath;
	}
	
	/**
	 * 生成上传文件保存路径
	 * 
	 * @param string $ext 扩展名
	 */
	public function makeSaveFile($ext)
	{
		return $this->savePath.date('YmdHis').'_'.mt_rand(100,999).".{$ext}";
	}
	
	/**
	 * 执行上传动作
	 * 
	 * @param string $field 表单项名称
	 * @param boolean $multi 是否同时上传多个，默认false
	 * @return array 返回上传结果
	 */
	public function execute($field, $multi = FALSE)
	{
		@set_time_limit(0);
		if ($multi && is_array($_FILES[$field]['name'])) {
			$files = array();
			$_FILES[$field]['name'] = array_unique($_FILES[$field]['name']); //去除重复
			foreach ($_FILES[$field]['name'] as $key => $value) {
				if (!empty($value)) {
					$files[] = "{$field}_{$key}";
					$_FILES["{$field}_{$key}"] = array(
						'name'     => $_FILES[$field]['name'][$key],
						'type'     => $_FILES[$field]['type'][$key],
						'tmp_name' => $_FILES[$field]['tmp_name'][$key],
						'error'    => $_FILES[$field]['error'][$key],
						'size'     => $_FILES[$field]['size'][$key],
					);
				}
			}
			unset($_FILES[$field]);
			$result = array();
			foreach ($files as $file) {
				$result[] = $this->execute($file, FALSE);
			}
			return $result;
		}
		
		$file = $_FILES[$field];
		$fileext = $this->fileext($file['name']);
		$result = array(
			'error' => '',
			'name'  => $file['name'],
			'path'  => '',
			'url'   => '',
			'size'  => $file['size'],
			'type'  => $file['type'],
			'ext'   => $fileext,
		);
		if ($file['error']) {
			$result['error'] = $this->errmsg($file['error']);
		} elseif ($file['size'] > $this->maxsize) {
			$result['error'] = $this->errmsg(-1);
		} elseif (!in_array($fileext, $this->allowTypes)) {
			$result['error'] = $this->errmsg(-2);
		} else {
			$file['tmp_name'] = str_replace('\\\\', '\\', $file['tmp_name']);
			if ( is_uploaded_file($file['tmp_name']) ) {
				$filepath = $this->makeSaveFile($fileext);
				if ( move_uploaded_file($file['tmp_name'], $filepath) ) {
					$result['path'] = $filepath;
					$result['url'] = $filepath;
					@unlink($file['tmp_name']);
				}
			}
		}

		return $result;
	}
	
	/**
	 * 按照指定规则创建上传目录
	 */
	private function makepath($path)
	{
		if (!is_dir($path)) {
			if (!is_dir(dirname($path))) {
				$this->makepath(dirname($path));
			}
			@mkdir($path);
			@chmod($path, 0777);
		}
	}
	
	/**
	 * 根据错误号返回错误消息
	 * 
	 * @param int $err
     * @return string
	 */
	private function errmsg($err)
	{
		$msg = '';
		switch ($err) {
			case 0: $msg = '';
			case 1: $msg = '文件超过了 php.ini 中 upload_max_filesize 选项限制的值。'; break;
			case 2: $msg = '上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值。'; break;
			case 3: $msg = '文件只有部分被上传。'; break;
			case 4: $msg = '没有文件被上传。'; break;
			case 6: $msg = '找不到临时文件夹。'; break;
			case 7: $msg = '文件写入失败。'; break;
			case -1: $msg = '文件大小超出限制。'; break;
			case -2: $msg = '文件类型不允许。'; break;
			default: $msg = 'unknow error';
		}
		return $msg;
	}

    /**
     * 返回小写文件扩展名
     *
     * @param $file
     * @return string
     */
    private function fileext($file)
	{
		return strtolower(pathinfo($file, PATHINFO_EXTENSION));
	}

}
