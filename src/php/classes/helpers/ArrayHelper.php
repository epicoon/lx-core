<?php

namespace lx;

/**
 * Class ArrayHelper
 * @package lx
 */
class ArrayHelper
{
	/**
	 * @param array $array
	 * @param string $field
	 * @return array
	 */
	public static function map($array, $field)
	{
		$result = [];

		foreach ($array as $value) {
			if (!is_array($value) || !array_key_exists($field, $value)) continue;
			$result[$value[$field]] = $value;
		}

		return $result;
	}

	/**
	 * @param static $key
	 * @param array $array
	 * @param mixed $default
	 * @return mixed
	 */
	public static function extract($key, &$array, $default = null)
	{
		if (!array_key_exists($key, $array)) {
			return $default;
		}

		$result = $array[$key];
		unset($array[$key]);
		return $result;
	}

	/**
	 * @param array $array
	 * @param array $path
	 * @return bool
	 */
	public static function pathExists($array, $path)
	{
		$currentArray = $array;
		foreach ($path as $key) {
			if (!is_array($currentArray) || !array_key_exists($key, $currentArray)) {
				return false;
			}

			$currentArray = $currentArray[$key];
		}

		return true;
	}

	/**
	 * @param array $array
	 * @param array $path
	 * @return array
	 */
	public static function createPath($array, $path)
	{
		$currentArray = &$array;
		foreach ($path as $key) {
			if (!is_array($currentArray)) {
				return $array;
			}

			if (!array_key_exists($key, $currentArray)) {
				$currentArray[$key] = [];
			}

			$currentArray = &$currentArray[$key];
		}

		return $array;
	}

	/**
	 * @param array $arr
	 * @return bool
	 */
	public static function deepEmpty($arr)
	{
		$rec = function ($arr) use (&$rec) {
			if (empty($arr)) {
				return true;
			}

			foreach ($arr as $value) {
				if (is_array($value)) {
					$empty = $rec($value);
					if (!$empty) {
						return false;
					}
				} else {
					return false;
				}
			}
		};
		return $rec($arr);
	}

	/**
	 * @param array $array1
	 * @param array $array2
	 * @param bool $rewrite
	 * @return array
	 */
	public static function mergeRecursiveDistinct($array1, $array2, $rewrite = false)
	{
		$merged = $array1;
		foreach ($array2 as $key => $value) {
			if (is_array($value) && array_key_exists($key, $merged) && is_array($merged[$key])) {
				$merged[$key] = self::mergeRecursiveDistinct($merged[$key], $value, $rewrite);
			} else {
				if (!array_key_exists($key, $merged) || $rewrite) {
					$merged[$key] = $value;
				}
			}
		}

		return $merged;
	}

	/**
	 * @param array $array
	 * @return bool
	 */
	public static function isAssoc($array)
	{
		$counter = 0;
		foreach ($array as $key => $value) {
			if ($key !== $counter++) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Method recieves array of accosiated arrays wirh the same keys
	 * Extract keys in the individual field
	 * Transfer arrays to countable with values order according to keys order
	 *
	 * @param array $arr
	 * @return array
	 */
	public static function valuesStable($arr)
	{
		$keys = array_keys($arr[0]);
		$rows = [];

		foreach ($arr as $row) {
			$current = [];
			foreach ($keys as $key) {
				$current[] = $row[$key];
			}
			$rows[] = $current;
		}

		return [
			'keys' => $keys,
			'rows' => $rows
		];
	}

	/**
	 * Convert array to JS-like code string
	 *
	 * @param array $array
	 * @return string
	 */
	public static function arrayToJsCode($array)
	{
		$rec = function ($val) use (&$rec) {
			// на рекурсию
			if (is_array($val)) {
				$arr = [];
				$keys = [];
				$assoc = false;
				foreach ($val as $key => $item) {
					$keys[] = $key;
					$arr[] = $rec($item);
					if (is_string($key)) $assoc = true;
				}
				if (!$assoc) return '[' . implode(',', $arr) . ']';

				$temp = [];
				foreach ($keys as $i => $key) {
					$temp[] = "'$key':{$arr[$i]}";
				}
				return '{' . implode(',', $temp) . '}';
			}

			if (is_string($val)) {
				if ($val == '') return '\'\'';
				if (preg_match('/\n/', $val)) {
					if (preg_match('/`/', $val)) {
						$val = preg_replace('/(?:\n|\r|\r\n)/', '$1\'+\'', $val);
					} else {
						$val = "`$val`";
						return $val;
					}
				}
				if ($val{0} != '\'') return "'$val'";
			}
			if ($val === true) return 'true';
			if ($val === false) return 'false';
			if ($val === null) return 'null';
			return $val;
		};

		$result = $rec($array);
		return $result;
	}

    /**
     * @param array $array
     * @param integer $level
     * @return string
     */
	public static function arrayToPhpCode($value, $level = 0)
    {
        if (empty($value)) {
            return '[]';
        }

        if (!is_array($value)) {
            return "'" .  addslashes($value) . "'";
        }

        $out = '';
        $tab = '    ';
        $margin = str_repeat($tab, $level++);

        $out .= '[' . PHP_EOL;
        foreach ($value as $key => $row) {
            $out .= $margin . $tab;
            if (is_numeric($key)) {
                $out .= $key . ' => ';
            } else {
                $out .= "'" . $key . "' => ";
            }

            if (is_array($row)) {
                $out .= self::arrayToPhpCode($row, $level);
            } elseif (is_null($row)) {
                $out .= 'null';
            } elseif (is_numeric($row)) {
                $out .= $row;
            } elseif ($row === true) {
                $out .= 'true';
            } elseif ($row === false) {
                $out .= 'false';
            } else {
                $out .= "'" . addslashes($row) . "'";
            }

            $out .= ',' . PHP_EOL;
        }

        $out .= $margin . ']';

        return $out;
    }
}
