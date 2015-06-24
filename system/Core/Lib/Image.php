<?php
/**
 * 图片处理类
 *
 * @copyright (c) 2013 www.lisijie.org
 * @author lisijie <lsj86@qq.com>
 * @version $Id: Image.php 67 2014-10-16 09:07:50Z lisijie $
 */

namespace Core\Lib;

class Image
{

    /**
     * 创建缩略图
     *
     * @param string $srcfile 源图片路径
     * @param string $imgfile 缩略图保存路径
     * @param int $width 缩略图宽度
     * @param int $height 缩略图高度
     * @param boolean $fix 是否固定尺寸（将会对图片进行裁剪）
     */
    public static function thumb($srcfile, $imgfile, $width, $height, $fix = false)
    {
        if (!$width || !$height) return false;
        if (!function_exists('gd_info')) return false;
        list($srcW, $srcH, $type) = getimagesize($srcfile);
        switch ($type) {
            case 1 :
                $src = imagecreatefromgif($srcfile);
                break;
            case 2 :
                $src = imagecreatefromjpeg($srcfile);
                break;
            case 3 :
                $src = imagecreatefrompng($srcfile);
                break;
            default:
                return false;
                break;
        }
        if (!$fix) {
            $scale = min($width / $srcW, $height / $srcH);
            $width = (int)$srcW * $scale;
            $height = (int)$srcH * $scale;
            $srcX = $srcY = 0;
        } else {
            if ($srcW / $srcH > $width / $height) {
                $srcX = ($srcW - $srcH * $width / $height) / 2;
                $srcY = 0;
                $srcW = $srcH * $width / $height;
            } else {
                $srcX = 0;
                $srcY = ($srcH - $srcW * $height / $width) / 2;
                $srcH = $srcW * $height / $width;
            }
        }
        if (function_exists('imagecreatetruecolor')) {
            $img = imagecreatetruecolor($width, $height);
            imagecopyresampled($img, $src, 0, 0, 0, 0, $width, $height, $srcW, $srcH);
        } else {
            $img = imagecreate($width, $height);
            imagecopyresized($img, $src, 0, 0, 0, 0, $width, $height, $srcW, $srcH);
        }
        if (is_file($imgfile)) {
            @unlink($imgfile);
        }
        $fileext = pathinfo($imgfile, PATHINFO_EXTENSION);
        switch ($fileext) {
            case 'gif' :
                imagegif($img, $imgfile);
                break;
            case 'png' :
                imagepng($img, $imgfile);
                break;
            default:
                imagejpeg($img, $imgfile);
                break;
        }
        imagedestroy($src);
        imagedestroy($img);
        return $imgfile;
    }

    /**
     * 添加图片水印
     *
     * @param string $srcfile 要添加水印的图片路径
     * @param string $markpic 水印图片路径
     * @param int $markpos 水印位置（默认右下角）
     * @param int $marktrans 水印透明度百分比
     * @return boolean 成功:true, 失败:false
     */
    public static function watermark($srcfile, $markpic, $markpos = 6, $marktrans = 100)
    {
        if (!is_file($srcfile)) return false;
        list($wmW, $wmH, $wmType) = getimagesize($markpic);
        switch ($wmType) {
            case 1 :
                $wm = imagecreatefromgif($markpic);
                break;
            case 2 :
                $wm = imagecreatefromjpeg($markpic);
                break;
            case 3 :
                $wm = imagecreatefrompng($markpic);
                break;
        }
        if (!$wm) return false;
        list($srcW, $srcH, $srcType) = getimagesize($srcfile);
        if ($wmW > $srcW || $wmH > $srcH) return false;
        switch ($srcType) {
            case 1 :
                $src = imagecreatefromgif($srcfile);
                break;
            case 2 :
                $src = imagecreatefromjpeg($srcfile);
                break;
            case 3 :
                $src = imagecreatefrompng($srcfile);
                break;
        }
        if (!$src) return false;
        //水印位置
        switch ($markpos) {
            case 1 : //顶部居左
                $srcX = 0;
                $srcY = 0;
                break;
            case 2 : //顶部居中
                $srcX = ($srcW - $wmW) / 2;
                $srcY = 0;
                break;
            case 3 : //顶部居右
                $srcX = $srcW - $wmW;
                $srcY = 0;
                break;
            case 4 : //底部居左
                $srcX = 0;
                $srcY = $srcH - $wmH;
                break;
            case 5 : //底部居中
                $srcX = ($srcW - $wmW) / 2;
                $srcY = $srcH - $wmH;
                break;
            case 6 : //底部居右
                $srcX = $srcW - $wmW;
                $srcY = $srcH - $wmH;
                break;
            default : //随机
                $srcX = mt_rand(0, $srcW - $wmW);
                $srcY = mt_rand(0, $srcH - $wmH);
        }
        if (function_exists('ImageAlphaBlending')) {
            imagealphablending($wm, true);
            imagesavealpha($wm, true);
        }
        imagecopy($src, $wm, $srcX, $srcY, 0, 0, $wmW, $wmH);
        switch ($srcType) {
            case 1 : //GIF
                imagegif($src, $srcfile);
                break;
            case 3 : //PNG
                imagepng($src, $srcfile);
                break;
            default : //默认JPG
                imagejpeg($src, $srcfile);
                break;
        }
        imagedestroy($src);
        imagedestroy($wm);
        return true;
    }

    /**
     * 为图片添加文字水印
     *
     * @param string $srcfile 图片文件路径
     * @param string $fontfile 字体文件路径
     * @param string $text 水印文字
     * @param int $size 水印文字字体大小
     * @param string $rgb 颜色十六进制RGB值，如:FF0000
     * @param int $markpos 水印位置
     * @param array $options 其他可选参数: angle:字体倾斜角度, shadow_x: 阴影X轴偏移量, shadow_y: 阴影Y轴偏移量, shadow_color: 阴影十六进制RGB值
     */
    public static function watermarkText($srcfile, $fontfile, $text, $size, $rgb, $markpos = 6, $options = array())
    {
        if (!is_file($srcfile) || !is_file($fontfile) || empty($text) || $size < 1) return false;
        list($srcW, $srcH, $srcType) = getimagesize($srcfile);
        switch ($srcType) {
            case 1 :
                $image = imagecreatefromgif($srcfile);
                break;
            case 2 :
                $image = imagecreatefromjpeg($srcfile);
                break;
            case 3 :
                $image = imagecreatefrompng($srcfile);
                break;
        }
        if (!$image) return false;

        //水印位置
        $offset = round($size / 3); //偏移量，不要紧挨着边
        $box = imagettfbbox($size, intval($options['angle']), $fontfile, $text);
        $textW = max($box[2], $box[4]) - min($box[0], $box[6]); //文本宽度
        $textH = max($box[1], $box[3]) - min($box[5], $box[7]); //文本高度
        switch ($markpos) {
            case 1 : //顶部居左
                $srcX = $offset;
                $srcY = $textH + $offset;
                break;
            case 2 : //顶部居中
                $srcX = ($srcW - $textW) / 2;
                $srcY = $textH + $offset;
                break;
            case 3 : //顶部居右
                $srcX = $srcW - $textW - $offset;
                $srcY = $textH + $offset;
                break;
            case 4 : //底部居左
                $srcX = $offset;
                $srcY = $srcH - $offset;
                break;
            case 5 : //底部居中
                $srcX = ($srcW - $textW) / 2;
                $srcY = $srcH - $offset;
                break;
            case 6 : //底部居右
                $srcX = $srcW - $textW - $offset;
                $srcY = $srcH - $offset;
                break;
            default : //随机
                $srcX = mt_rand(0, $srcW - $textW);
                $srcY = mt_rand(0, $srcH - $textH);
        }

        //添加文字阴影
        if ($options['shadow_x'] && $options['shadow_y'] && $options['shadow_color']) {
            $shadowRGB = str_split(trim($options['shadow_color'], '#'), 2);
            $shadowcolor = imagecolorallocate($image, hexdec($shadowRGB[0]), hexdec($shadowRGB[1]), hexdec($shadowRGB[2]));
            imagettftext($image, $size, intval($options['angle']), $srcX + $options['shadow_x'], $srcY + $options['shadow_y'], $shadowcolor, $fontfile, $text);
        }

        $rgb = str_split(trim($rgb, '#'), 2);
        $color = imagecolorallocate($image, hexdec($rgb[0]), hexdec($rgb[1]), hexdec($rgb[2]));
        imagettftext($image, $size, intval($options['angle']), $srcX, $srcY, $color, $fontfile, $text);

        switch ($srcType) {
            case 1 :
                imagegif($image, $srcfile);
                break;
            case 3 :
                imagepng($image, $srcfile);
                break;
            default :
                imagejpeg($image, $srcfile);
        }
        imagedestroy($image);
        return true;
    }

}
