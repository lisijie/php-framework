<?php
namespace Core\Http;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * 通过HTTP上传的文件值对象
 *
 * @package Core\Http
 */
class UploadedFile implements UploadedFileInterface
{

    private static $errors = [
        UPLOAD_ERR_OK,
        UPLOAD_ERR_CANT_WRITE,
        UPLOAD_ERR_EXTENSION,
        UPLOAD_ERR_FORM_SIZE,
        UPLOAD_ERR_INI_SIZE,
        UPLOAD_ERR_NO_FILE,
        UPLOAD_ERR_NO_TMP_DIR,
        UPLOAD_ERR_PARTIAL,
    ];

    /**
     * 上传到服务器的文件路径
     * @var string
     */
    private $file;

    /**
     * 文件名
     * @var string
     */
    private $name;

    /**
     * 文件类型
     * @var string
     */
    private $type;

    /**
     * 文件大小
     * @var int
     */
    private $size;

    /**
     * 错误码
     * @var int
     */
    private $error;

    /**
     * 是否SAPI
     * @var bool
     */
    private $sapi;

    /**
     * @var StreamInterface
     */
    private $stream;

    /**
     * 是否已经移走
     * @var bool
     */
    private $moved = false;

    public function __construct($file, $name = null, $type = null, $size = null, $error = UPLOAD_ERR_OK, $sapi = false)
    {
        if (!in_array($error, self::$errors)) {
            throw new \InvalidArgumentException('Invalid error status.');
        }
        if (null !== $size && false === is_int($size)) {
            throw new \InvalidArgumentException('Upload file size must be an integer');
        }
        $this->file = $file;
        $this->name = $name;
        $this->type = $type;
        $this->size = $size;
        $this->error = $error;
        $this->sapi = $sapi;
    }

    /**
     * 获取上传文件的流对象
     *
     * @return StreamInterface
     * @throws \RuntimeException
     */
    public function getStream()
    {
        if ($this->moved) {
            throw new \RuntimeException(sprintf('Uploaded file %1s has already been moved', $this->name));
        }
        if ($this->stream === null) {
            $this->stream = new Stream(fopen($this->file, 'r'));
        }
        return $this->stream;
    }

    /**
     * 移动上传文件
     *
     * @param string $targetPath
     */
    public function moveTo($targetPath)
    {
        if ($this->moved) {
            throw new \RuntimeException('Uploaded file already moved');
        }
        $targetIsStream = strpos($targetPath, '://') > 0;
        if (!$targetIsStream && !is_writable(dirname($targetPath))) {
            throw new \RuntimeException('Upload target path is not writable');
        }
        if ($targetIsStream) {
            if (!copy($this->file, $targetPath)) {
                throw new \RuntimeException("Error moving uploaded file {$this->name} to {$targetPath}");
            }
            if (!unlink($this->file)) {
                throw new \RuntimeException("Error removing uploaded file {$this->file}");
            }
        } elseif ($this->sapi) {
            if (!is_uploaded_file($this->file)) {
                throw new \RuntimeException("{$this->file} is not a valid uploaded file");
            }
            if (!move_uploaded_file($this->file, $targetPath)) {
                throw new \RuntimeException("Error moving uploaded file {$this->name} to {$targetPath}");
            }
        } else {
            if (!rename($this->file, $targetPath)) {
                throw new \RuntimeException("Error moving uploaded file {$this->name} to {$targetPath}");
            }
        }
        $this->moved = true;
    }

    /**
     * 获取上传文件大小
     *
     * @return int|null
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * 获取错误码
     *
     * @return int
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * 获取源文件名
     *
     * @return string|null
     */
    public function getClientFilename()
    {
        return $this->name;
    }

    /**
     * 获取媒体类型
     *
     * @return string|null
     */
    public function getClientMediaType()
    {
        return $this->type;
    }
}