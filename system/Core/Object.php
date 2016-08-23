<?php
namespace Core;

class Object
{
	public static function className($shortName = false)
	{
		$className = get_called_class();
		if ($shortName) {
			$className = explode('\\', $className);
			for ($i = 0; $i < count($className) - 1; $i++) {
				$className[$i] = $className[$i][0];
			}
			$className = implode('.', $className);
		}
		return $className;
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

		return get_class($this) . '{'.implode(', ', $vars).'}';
	}
}