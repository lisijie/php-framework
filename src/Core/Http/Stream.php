<?php
namespace Core\Http;

use Psr\Http\Message\StreamInterface;

/**
 * 流操作类
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Http
 */
class Stream implements StreamInterface
{
    private $stream;
    private $readable;
    private $writable;
    private $seekable;
    private $size;
    private $uri;

    private static $readWriteMode = [
        'read' => [
            'r' => true, 'w+' => true, 'r+' => true, 'x+' => true, 'c+' => true,
            'rb' => true, 'w+b' => true, 'r+b' => true, 'x+b' => true,
            'c+b' => true, 'rt' => true, 'w+t' => true, 'r+t' => true,
            'x+t' => true, 'c+t' => true, 'a+' => true
        ],
        'write' => [
            'w' => true, 'w+' => true, 'rw' => true, 'r+' => true, 'x+' => true,
            'c+' => true, 'wb' => true, 'w+b' => true, 'r+b' => true,
            'x+b' => true, 'c+b' => true, 'w+t' => true, 'r+t' => true,
            'x+t' => true, 'c+t' => true, 'a' => true, 'a+' => true
        ]
    ];

    public function __construct($stream)
    {
        if (!is_resource($stream)) {
            throw new \InvalidArgumentException('stream must be a resource');
        }
        $this->stream = $stream;
        $meta = stream_get_meta_data($stream);
        $this->seekable = (bool)$meta['seekable'];
        $this->readable = isset(self::$readWriteMode['read'][$meta['mode']]);
        $this->writable = isset(self::$readWriteMode['write'][$meta['mode']]);
        $this->uri = isset($meta['uri']) ? $meta['uri'] : null;
    }

    public function __toString()
    {
        try {
            $this->seek(0);
            $contents = (string)stream_get_contents($this->stream);
            return $contents;
        } catch (\Exception $e) {
        }
        return '';
    }

    public function close()
    {
        if (isset($this->stream)) {
            if (is_resource($this->stream)) {
                fclose($this->stream);
            }
            $this->detach();
        }
    }

    public function detach()
    {
        if (!$this->stream) {
            return null;
        }
        $result = $this->stream;
        unset($this->stream);
        $this->size = $this->uri = null;
        $this->readable = $this->writable = $this->seekable = null;
        return $result;
    }

    public function getSize()
    {
        if ($this->size !== null) {
            return $this->size;
        }
        if (!$this->stream) {
            return null;
        }
        if ($this->uri) {
            clearstatcache(true, $this->uri);
        }
        $stats = fstat($this->stream);
        if (isset($stats['size'])) {
            $this->size = $stats['size'];
            return $this->size;
        }
        return null;
    }

    public function tell()
    {
        $result = ftell($this->stream);
        if ($result === false) {
            throw new \RuntimeException('Unable to determine stream position');
        }
        return $result;
    }

    public function eof()
    {
        return !$this->stream || feof($this->stream);
    }

    public function isSeekable()
    {
        return $this->seekable;
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        if (!$this->seekable) {
            throw new \RuntimeException('Stream is not seekable');
        } elseif (fseek($this->stream, $offset, $whence) === -1) {
            throw new \RuntimeException('Unable to seek to stream position '
                . $offset . ' with whence ' . var_export($whence, true));
        }
    }

    public function rewind()
    {
        $this->seek(0);
    }

    public function isWritable()
    {
        return $this->writable;
    }

    public function write($string)
    {
        if (!$this->writable) {
            throw new \RuntimeException('Cannot write to a non-writable stream');
        }
        $this->size = null;
        $result = fwrite($this->stream, $string);
        if ($result === false) {
            throw new \RuntimeException('Unable to write to stream');
        }
        return $result;
    }

    public function isReadable()
    {
        return $this->readable;
    }

    public function read($length)
    {
        if (!$this->readable) {
            throw new \RuntimeException('Cannot read from non-readable stream');
        }
        if ($length < 0) {
            throw new \RuntimeException('Length parameter cannot be negative');
        }
        if (0 === $length) {
            return '';
        }
        $string = fread($this->stream, $length);
        if (false === $string) {
            throw new \RuntimeException('Unable to read from stream');
        }

        return $string;
    }

    public function getContents()
    {
        $contents = stream_get_contents($this->stream);
        if ($contents === false) {
            throw new \RuntimeException('Unable to read stream contents');
        }
        return $contents;
    }

    public function getMetadata($key = null)
    {
        if (!$this->stream) {
            return $key ? '' : [];
        } elseif (!$key) {
            return stream_get_meta_data($this->stream);
        }
        $meta = stream_get_meta_data($this->stream);
        return isset($meta[$key]) ? $meta[$key] : null;
    }

    public function __destruct()
    {
        $this->close();
    }
}