<?php
namespace Core\Lib;

/**
 * 文件上传类
 *
 * 配置项说明：
 *  - allow_types 允许上传的文件类型，多个用"|"分隔，如：jpg|png|gif
 *  - save_path 上传目录，可以使用 {Y}、{y}、{m}、{d} 作为日期变量
 *  - maxsize 最大允许上传的文件大小，单位KB
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Lib
 */
class Upload
{

    // 上传目录
    private $savePath = 'upload/';
    // 允许类型
    private $allowTypes = array();
    // 文件大小上限
    private $maxsize = 0;
    //错误消息
    private $message = array(
        0 => '上传成功',
        1 => '上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值',
        2 => '上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值',
        3 => '文件只有部分被上传',
        4 => '文件没有被上传',
        5 => '找不到临时文件夹',
        6 => '文件写入失败',
        -1 => '文件大小超出限制',
        -2 => '文件类型不允许',
    );

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
        $this->savePath = str_replace(array('{y}', '{Y}', '{m}', '{d}', '\\', '..'), array(date('y'), date('Y'), date('m'), date('d'), '/', ''), $this->savePath);
        if (substr($this->savePath, -1) != '/') $this->savePath .= '/';
        $this->maxsize *= 1024; //最大允许上传的文件大小/byte
        if (!is_dir($this->savePath)) {
            mkdir($this->savePath, 0755, true); //创建目录
        }
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
     * @return string
     */
    public function makeSaveFile($ext)
    {
        return $this->savePath . date('YmdHis') . '_' . mt_rand(1, 99999) . ".{$ext}";
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
                        'name' => $_FILES[$field]['name'][$key],
                        'type' => $_FILES[$field]['type'][$key],
                        'tmp_name' => $_FILES[$field]['tmp_name'][$key],
                        'error' => $_FILES[$field]['error'][$key],
                        'size' => $_FILES[$field]['size'][$key],
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
            'name' => $file['name'],
            'path' => '',
            'size' => $file['size'],
            'type' => $file['type'],
            'ext' => $fileext,
        );
        if ($file['error']) {
            $result['error'] = $this->errmsg($file['error']);
        } elseif ($file['size'] > $this->maxsize) {
            $result['error'] = $this->errmsg(-1);
        } elseif (!in_array($fileext, $this->allowTypes)) {
            $result['error'] = $this->errmsg(-2);
        } else {
            $file['tmp_name'] = str_replace('\\\\', '\\', $file['tmp_name']);
            if (is_uploaded_file($file['tmp_name'])) {
                $filepath = $this->makeSaveFile($fileext);
                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    $result['path'] = $filepath;
                }
            }
        }

        return $result;
    }

    /**
     * 根据错误号返回错误消息
     *
     * @param int $code
     * @return string
     */
    private function errmsg($code)
    {
        return isset($this->message[$code]) ? $this->message[$code] : '未知错误';
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
