<?php
namespace Core\Web\Debug\Lib;

use Core\Lib\FileHelper;

class Storage
{
    private $savePath;

    public function __construct()
    {
        $this->savePath = DATA_PATH . '/debug';
        if (!is_dir($this->savePath)) {
            mkdir($this->savePath, 0755, true);
        }
    }

    public function save(array $data)
    {
        $fileKey = uniqid();
        $data = json_encode($data);
        $filename = $this->savePath . '/' . $fileKey . '.dat';
        file_put_contents($filename, $data);
        return $fileKey;
    }

    public function get($fileKey)
    {
        $filename = $this->savePath . '/' . $fileKey . '.dat';
        if (!is_file($filename)) {
            return false;
        }
        $data = file_get_contents($filename);
        $data = json_decode($data, true);
        return $data;
    }

    public function clear()
    {
        FileHelper::removeDir($this->savePath);
    }
}