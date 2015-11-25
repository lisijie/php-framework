<?php

namespace Core\Cache\Driver;

use Core\Cache\Cache;
use Core\Cache\CacheInterface;
use Core\Cache\CacheException;

/**
 * 文件缓存
 *
 * 配置:
 * $options = array(
 *        'prefix' => KEY前缀，用于目录hash
 *        'save_path' => 缓存文件存放目录
 * )
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Cache
 */
class File extends Cache implements CacheInterface
{

    protected $savePath = '';

    public function __construct($options)
    {
        $this->prefix = isset($options['prefix']) ? $options['prefix'] : '';
        if (empty($options['save_path'])) {
            throw new CacheException("缺少参数: save_path");
        }
        $this->savePath = $options['save_path'];
        if (!is_dir($options['save_path']) && !@mkdir($options['save_path'], 0755, true)) {
            throw new CacheException("日志目录创建失败: {$options['save_path']}");
        }
    }

    public function add($key, $value, $seconds = 0)
    {
        $filename = $this->filename($key);
        if (!is_file($filename) || false === $this->get($key)) {
            return $this->set($key, $value, $seconds);
        }
        return false;
    }

    public function set($key, $value, $seconds = 0)
    {
        $file = $this->filename($key);
        if (!is_dir(dirname($file))) mkdir(dirname($file), 0755, true);
        $value = serialize($value);
        $expire = $seconds == 0 ? 0 : NOW + $seconds;
        $compress = (strlen($value) > 512 && function_exists('gzcompress')) ? 1 : 0;
        if ($compress) {
            $value = gzcompress($value, 3);
        }
        $value = "<?php die;?>\n" . pack('V', $expire) . $compress . $value;
        return file_put_contents($file, $value);
    }

    public function get($key)
    {
        $file = $this->filename($key);
        if (is_file($file)) {
            $data = file_get_contents($file);
            if (!empty($data)) {
                list(, $expire) = unpack('V', substr($data, 13, 4));
                $compress = substr($data, 17, 1);
                if (($expire > 0 && NOW > $expire) || ($compress && !function_exists('gzcompress'))) {
                    unlink($file);
                    return false;
                }
                $data = substr($data, 18);
                if ($compress) $data = gzuncompress($data);
                $data = unserialize($data);
                return $data;
            }
        }
        return false;
    }

    public function rm($key)
    {
        $file = $this->filename($key);
        return @unlink($file);
    }

    public function flush()
    {
        $this->removeDir($this->savePath);
    }

    private function filename($key)
    {
        $hash = md5($this->prefix . $key);
        $path = $this->savePath . substr($hash, 0, 2) . '/' . substr($hash, 2, 2);
        return $path . '/' . $hash . '.php';
    }

    private function removeDir($path)
    {
        $path = rtrim($path, DIRECTORY_SEPARATOR);
        if (($handle = opendir($path)) !== false) {
            while (false !== ($d = readdir($handle))) {
                if ($d != '.' && $d != '..') {
                    $p = $path . DIRECTORY_SEPARATOR . $d;
                    if (is_dir($p)) {
                        $this->removeDir($p);
                        @rmdir($p);
                    } else {
                        @unlink($p);
                    }
                }
            }
            closedir($handle);
        }
    }

    public function increment($key, $value = 1)
    {
        throw new CacheException("文件缓存不支持自增操作");
    }

    public function decrement($key, $value = 1)
    {
        throw new CacheException("文件缓存不支持自减操作");
    }
}
