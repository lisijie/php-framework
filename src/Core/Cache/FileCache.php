<?php
namespace Core\Cache;

use Core\Lib\FileHelper;

/**
 * 文件缓存
 *
 * 文件缓存并不能保证add操作的原子性，慎用！！！
 *
 * 适用于单机环境。
 * 配置:
 * $options = [
 *        'save_path' => '/tmp/cache', // 缓存文件存放目录
 * ]
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
        if (empty($this->config['save_path'])) {
            throw new CacheException("缺少参数: save_path");
        }
        $this->savePath = rtrim($this->config['save_path'], '/');
        if (!is_dir($this->config['save_path']) && !@mkdir($this->config['save_path'], 0755, true)) {
            throw new CacheException("日志目录创建失败: {$this->config['save_path']}");
        }
    }

    protected function doAdd($key, $value, $ttl = 0)
    {
        $filename = $this->filename($key);
        if (!is_dir(dirname($filename))) {
            @mkdir(dirname($filename), 0755, true);
        }
        @touch($filename);
        $fp = @fopen($filename, 'r+');
        if (!$fp) {
            return false;
        }
        if (flock($fp, LOCK_EX | LOCK_NB)) {
            $content = fread($fp, 18);
            $result = $this->decode($content);
            if (isset($result['expired']) && !$result['expired']) {
                fclose($fp);
                return false;
            }
            $content = $this->encode($value, $ttl);
            fwrite($fp, $content);
            fflush($fp);
            fclose($fp);
            return true;
        }
        return false;
    }

    private function doSet($key, $value, $ttl = 0)
    {
        $file = $this->filename($key);
        if (!is_dir(dirname($file))) {
            @mkdir(dirname($file), 0755, true);
        }
        $content = $this->encode($value, $ttl);
        file_put_contents($file, $content, LOCK_EX);
        return true;
    }

    protected function doSetMultiple(array $values, $ttl = 0)
    {
        foreach ($values as $key => $value) {
            if (!$this->doSet($key, $value, $ttl)) {
                return false;
            }
        }
        return true;
    }

    protected function doGet($key, $default = null)
    {
        $file = $this->filename($key);
        if (is_file($file)) {
            $content = file_get_contents($file);
            if (!empty($content)) {
                $result = $this->decode($content);
                if ($result['expired']) {
                    @unlink($file);
                    return $default;
                }
                return $result['data'];
            }
        }
        return $default;
    }

    protected function doGetMultiple(array $keys, $default = null)
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->doGet($key, $default);
        }
        return $result;
    }

    protected function doDeleteMultiple(array $keys)
    {
        foreach ($keys as $key) {
            $file = $this->filename($key);
            @unlink($file);
        }
        return true;
    }

    protected function doIncrement($key, $step = 1)
    {
        if ($step < 1) {
            $step = 1;
        }
        $filename = $this->filename($key);
        if (!is_dir(dirname($filename))) {
            @mkdir(dirname($filename), 0755, true);
        }
        @touch($filename);
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
            @mkdir(dirname($filename), 0755, true);
        }
        @touch($filename);
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
        $hash = md5($key);
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
            if ($data) {
                if ($compress) $data = gzuncompress($data);
                $data = @unserialize($data);
            }
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

    protected function doClear()
    {
        return FileHelper::removeDir($this->savePath);
    }

    protected function doHas($key)
    {
        $filename = $this->filename($key);
        if (!is_file($filename)) {
            return false;
        }
        $content = file_get_contents($filename);
        $result = $this->decode($content);
        if (!$result || $result['expired']) {
            unlink($filename);
            return false;
        }
        return true;
    }
}
