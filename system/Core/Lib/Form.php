<?php
namespace Core\Lib;

class Form
{

    /**
     * 单行输入框
     *
     * 创建一个单行输入框
     * @param string $name 控件名称
     * @param string $value 默认值
     * @param array $attr 其他属性
     * @return string
     */
    public static function input($name, $value = '', $attr = array())
    {
        if (!isset($attr['type'])) $attr['type'] = 'text';
        $id = str_replace(array('[', ']'), '_', $name);
        $string = '<input name="' . $name . '" id="' . $id . '" value="' . $value . '" ' . static::makeAttr($attr) . '/>' . "\r\n";
        return $string;
    }

    /**
     * 多行输入框
     *
     * 创建一个单行输入框
     * @param string $name 控件名称
     * @param string $value 默认值
     * @param array $attr 其他属性
     * @return string
     */
    public static function textarea($name, $value = '', $attr = array())
    {
        $string = '<textarea name="' . $name . '" ' . static::makeAttr($attr) . '>' . $value . '</textarea>' . "\r\n";
        return $string;
    }

    /**
     * 编辑器
     *
     * 创建一个编辑器实例
     * @param string $name 控件名称
     * @param string $value 默认值
     * @param string $mode 模式(full|simple)，默认:full
     * @return string
     */
    public static function editor($name, $value = '', $mode = 'full', $attr = array())
    {
        if (!defined('EDITOR_INIT')) {
            $string = '
			<script type="text/javascript" charset="utf-8">
		        window.UEDITOR_HOME_URL = "/static/ueditor/";
		    </script>
			<script type="text/javascript" src="/static/ueditor/ueditor.config.js"></script>
			<script type="text/javascript" src="/static/ueditor/ueditor.all.min.js"></script>
			';
            define('EDITOR_INIT', TRUE);
        }
        $options = array();
        $options['imageUrl'] = isset($attr['imageUrl']) ? $attr['imageUrl'] : '';
        $options['fileUrl'] = isset($attr['fileUrl']) ? $attr['fileUrl'] : '';
        $options['imagePath'] = $options['filePath'] = '';
        if ($mode == 'simple') {
            $options['initialFrameWidth'] = isset($attr['width']) ? $attr['width'] : '500';
            $options['initialFrameHeight'] = isset($attr['height']) ? $attr['height'] : '200';
            $options['toolbars'] = array(array("bold", "italic", "underline", "strikethrough", "forecolor", "backcolor", "insertorderedlist", "insertunorderedlist", "justifyleft", "justifycenter", "justifyright", "justifyjustify", "fontfamily", "fontsize"));
        } else {
            $options['initialFrameWidth'] = isset($attr['width']) ? $attr['width'] : '90%';
            $options['initialFrameHeight'] = isset($attr['height']) ? $attr['height'] : '400';
        }
        $string .= '
		<script type="text/plain" id="' . $name . '" name="' . $name . '">' . $value . '</script>
		<script type="text/javascript" charset="utf-8">
			var options = ' . json_encode($options) . ';
			var ue = UE.getEditor("' . $name . '", options);
		</script>
		';
        return $string;
    }

    /**
     * 单选框
     *
     * 创建一组单选框
     *
     * @param string $name 控件名称
     * @param string $value 默认值
     * @param array $options 选项列表 array('值'=>'描述')
     * @return string
     */
    public static function radio($name, $value = '', $options = array())
    {
        $string = '';
        foreach ($options as $key => $val) {
            $id = str_replace(array('[', ']'), '_', $name) . $key;
            $chk = $value == $key ? ' checked' : '';
            $string .= '<input type="radio" name="' . $name . '" value="' . $key . '" id="' . $id . '"' . $chk . ' /><label for="' . $id . '" class="help-inline">' . $val . '</label>&nbsp;&nbsp;';
        }
        return $string;
    }

    /**
     * 创建一个下拉列表框
     *
     * @param string $name 控件名称
     * @param string $value 默认值
     * @param array $options 选项列表 array('值'=>'描述')
     * @return string
     */
    public static function select($name, $value, $options = array(), $attr = array())
    {
        $string = "<select name=\"{$name}\" " . self::makeAttr($attr) . ">\n";
        foreach ($options as $key => $val) {
            $chk = $value == $key ? ' selected' : '';
            $string .= "<option value=\"{$key}\" {$chk}>{$val}</option>\n";
        }
        $string .= "</select>\n";
        return $string;
    }

    /**
     * 复选框
     *
     * 创建一个复选框
     * @param string $name 控件名称
     * @param string $value 默认值
     * @param string $desc 描述
     * @param boolean $checked 默认是否选中
     * @return string
     */
    public static function checkbox($name, $value, $desc, $checked = false)
    {
        $id = str_replace(array('[', ']'), '_', $name) . $value;
        $string = '<input type="checkbox" name="' . $name . '" value="' . $value . '" id="' . $id . '" ' . ($checked ? 'checked' : '') . ' />';
        $string .= '<label for="' . $id . '" class="help-inline">' . $desc . '</label>' . "\r\n";
        return $string;
    }

    private static function makeAttr($attr)
    {
        if (is_string($attr)) return $attr;
        $at = '';
        foreach ($attr as $key => $val) {
            $at .= "{$key}=\"{$val}\" ";
        }
        return $at;
    }
}
