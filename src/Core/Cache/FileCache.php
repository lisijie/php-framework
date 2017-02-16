<?php

namespace Core\Cache;

/**
 * 文件缓存
 *
 * 适用于单机环境。
 * 配置:
 * $options = array(
 *        'prefix' => 'test_', // KEY前缀，用于目录hash
 *        'save_path' => '/tmp/cache', // 缓存文件存放目录
 * )
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Cache
 */
class FileCache extends AbstractCache
{
    // 保存目录
    protected $savePath = '';

    // 值长度达到多少字节进行压缩
    private $compressSize = 512;

    public function init()
    {
        $this->prefix = isset($this->config['prefix']) ? $this->config['prefix'] : '';
        if (empty($this->config['save_path'])) {
            throw new CacheException("缺少参数: save_path");
        }
        $this->savePath = rtrim($this->config['save_path'], '/');
        if (!is_dir($this->config['save_path']) && !@mkdir($this->config['save_path'], 0755, true)) {
            throw new CacheException("日志目录创建失败: {$this->config['save_path']}");
        }
    }

    protected function doAdd($key, $value, $ttl)
    {
        $filename = $this->filename($key);
        if (!is_file($filename) || false === $this->get($key)) {
            return $this->set($key, $value, $ttl);
        }
        return false;
    }

    protected function doSet($key, $value, $ttl)
    {
        $file = $this->filename($key);
        if (!is_dir(dirname($file))) mkdir(dirname($file), 0755, true);
        $content = $this->encode($value, $ttl);
        return file_put_contents($file, $content, LOCK_EX) > 0;
    }

    protected function doMSet(array $array, $ttl)
    {
        $count = 0;
        foreach ($array as $key => $value) {
            if ($this->doSet($key, $value, $ttl)) {
                $count++;
            }
        }
        return $count;
    }

    protected function doGet($key)
    {
        $file = $this->filename($key);
        if (is_file($file)) {
            $content = file_get_contents($file);
            if (!empty($content)) {
                $result = $this->decode($content);
                if ($result['expired']) {
                    @unlink($file);
                    return false;
                }
                return $result['data'];
            }
        }
        return false;
    }

    protected function doMGet(array $keys)
    {
        $result = [];
        foreach ($keys as $key) {
            if (false !== ($value = $this->doGet($key))) {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    protected function doDel(array $keys)
    {
        $count = 0;
        foreach ($keys as $key) {
            $file = $this->filename($key);
            if (is_file($file) && unlink($file)) {
                $count++;
            }
        }
        return $count;
    }

    protected function doIncrement($key, $step = 1)
    {
        if ($step < 1) {
            $step = 1;
        }
        $filename = $this->filename($key);
        if (!is_dir(dirname($filename))) {
            mkdir(dirname($filename), 0755, true);
        }
        touch($filename);
        $file = new \SplFileObject($filename, 'r+');
        if ($file->flock(LOCK_EX)) {
            if ($file->getSize() == 0) {
                $value = 0;
            } else {
                $content = $file->fread($file->getSize());
                $data = $this->decode($content);
                if ($data['expired']) {
                    $value = 0;
                } else {
                    $value = is_numeric($data['data']) ? intval($data['data']) : 0;
                }
            }
            $value += $step;
            $file->rewind();
            $file->ftruncate(0);
            $file->fwrite($this->encode($value, 0));
            $file->fflush();
            $file->flock(LOCK_UN);
            return $value;
        }
        return 0;
    }

    protected function doDecrement($key, $step = 1)
    {
        if ($step < 1) {
            $step = 1;
        }
        $filename = $this->filename($key);
        if (!is_dir(dirname($filename))) {
            mkdir(dirname($filename), 0755, true);
        }
        touch($filename);
        $file = new \SplFileObject($filename, 'r+');
        if ($file->flock(LOCK_EX)) {
            if ($file->getSize() == 0) {
                $value = 0;
            } else {
                $content = $file->fread($file->getSize());
                $data = $this->decode($content);
                if ($data['expired']) {
                    $value = 0;
                } else {
                    $value = is_numeric($data['data']) ? intval($data['data']) : 0;
                }
            }
            $value -= $step;
            $file->rewind();
            $file->ftruncate(0);
            $file->fwrite($this->encode($value, 0));
            $file->fflush();
            $file->flock(LOCK_UN);
            return $value;
        }
        return 0;
    }

    private function filename($key)
    {
        $hash = md5($this->prefix . $key);
        $path = $this->savePath . '/' . substr($hash, 0, 2) . '/' . substr($hash, 2, 2);
        return $path . '/' . $hash . '.php';
    }

    private function decode($content)
    {
        if (empty($content)) {
            return false;
        }
        list(, $expire) = unpack('V', substr($content, 13, 4));
        $compress = substr($content, 17, 1);
        $isExpired = $expire > 0 && time() > $expire;
        if ($isExpired || ($compress && !function_exists('gzcompress'))) {
            $data = false;
        } else {
            $data = substr($content, 18);
            if ($compress) $data = gzuncompress($data);
            $data = unserialize($data);
        }
        return ['expired' => $isExpired, 'data' => $data];
    }

    private function encode($value, $ttl)
    {
        $value = serialize($value);
        $expire = $ttl == 0 ? 0 : time() + $ttl;
        $compress = (strlen($value) >= $this->compressSize && function_exists('gzcompress')) ? 1 : 0;
        if ($compress) {
            $value = gzcompress($value, 3);
        }
        $result = "<?php die;?>\n" . pack('V', $expire) . $compress . $value;
        return $result;
    }
}
