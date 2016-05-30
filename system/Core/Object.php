<?php
namespace Core;

class Object
{
	public static function className()
	{
		return get_called_class();
	}

	public function __toString()
	{
		$vars = [];
		foreach (get_object_vars($this) as $key => $val) {
			if (is_string($val)) {
				$vars[] = "{$key}=\"".strval($val)."\"";
			} elseif (is_array($val)) {
				$vars[] = "{$key}=".json_encode($val);
			} else {
				$vars[] = "{$key}=".strval($val);
			}
		}

		return __CLASS__ . '{'.implode(', ', $vars).'}';
	}
}