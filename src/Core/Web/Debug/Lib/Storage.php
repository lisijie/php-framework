<?php
namespace Core\Web\Debug\Lib;

use Core\Lib\FileHelper;

class Storage
{
    private $savePath;

    private $idxFile;

    public function __construct()
    {
        $this->savePath = DATA_PATH . '/debug';
        if (!is_dir($this->savePath)) {
            mkdir($this->savePath, 0755, true);
        }
        $this->idxFile = $this->savePath . '/index.dat';
    }

    public function save(array $data)
    {
        $fileKey = uniqid();

        $idx = $fileKey . '|' . $data['meta']['requestTime'] . '|' . $data['meta']['method'] . '|' . $data['meta']['execTime'] .
            '|' . $data['meta']['memoryUsage'] . '|' . $data['meta']['url'];
        file_put_contents($this->idxFile, $idx . "\n", FILE_APPEND | LOCK_EX);

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

    public function getList($start, $size, &$total)
    {
        if (!is_file($this->idxFile)) {
            $total = 0;
            return [];
        }
        $lines = file($this->idxFile);
        $total = count($lines);
        $slice = array_slice($lines, $start, $size);
        $result = [];
        for ($i = count($slice) - 1; $i >= 0; $i --) {
            $line = $slice[$i];
            list($id, $time, $method, $execTime, $memory, $url) = explode('|', $line);
            $result[] = [
                'id' => $id,
                'time' => $time,
                'method' => $method,
                'exec_time' => $execTime,
                'memory' => $memory,
                'url' => $url,
            ];
        }
        return $result;
    }

    public function clear()
    {
        FileHelper::removeDir($this->savePath);
    }
}