<?php

namespace lx;

class ArrayHelper
{
    public static function iterableToArray(iterable $iterable): array
    {
        if (is_array($iterable)) {
            return $iterable;
        }

        if ($iterable instanceof ArrayInterface) {
            return $iterable->toArray();
        }

        if (is_object($iterable) && method_exists($iterable, 'toArray')) {
            return $iterable->toArray();
        }

        $result = [];
        foreach ($iterable as $key => $item) {
            $result[$key] = $value;

        }
        return $result;
    }
    
    /**
     * @param mixed $array
     */
    public static function isEmpty($array): bool
    {
        if ($array === null || $array === '') {
            return true;
        }

        if (is_array($array)) {
            return empty($array);
        }

        if ($array instanceof ArrayInterface) {
            return $array->isEmpty();
        }
        
        if (is_object($array) && method_exists($array, 'toArray')) {
            return empty($array->toArray());
        }
        
        if (is_iterable($array)) {
            foreach ($array as $item) {
                return false;
            }
            
            return true;
        }
        
        return false;
    }
    
    public static function merge(iterable $arr1, iterable $arr2): array
    {
        return array_merge(
            self::iterableToArray($arr1),
            self::iterableToArray($arr2)
        );
    }

	public static function map(iterable $array, string $field): array
	{
		$result = [];

		foreach ($array as $value) {
			if (!(is_iterable($value)) || !isset($value[$field])) {
			    continue;
            }
			$result[$value[$field]] = $value;
		}

		return $result;
	}

	/**
	 * @param mixed $default
	 * @return mixed
	 */
	public static function extract(string $key, iterable &$array, $default = null)
	{
		if (!isset($array[$key])) {
			return $default;
		}

		$result = $array[$key];
		unset($array[$key]);
		return $result;
	}

	public static function getColumn(iterable $array, string $columnName): array
    {
        $result = [];
        foreach ($array as $row) {
            if ((is_iterable($row)) && isset($row[$columnName])) {
                $result[] = $row[$columnName];
            }
        }
        return $result;
    }

	public static function pathExists(iterable $array, array $path): bool
	{
		$currentArray = $array;
		foreach ($path as $key) {
			if (!(is_iterable($currentArray)) || !isset($currentArray[$key])) {
				return false;
			}

			$currentArray = $currentArray[$key];
		}

		return true;
	}

	public static function createPath(iterable $array, iterable $path): iterable
	{
		$currentArray = &$array;
		foreach ($path as $key) {
			if (!(is_iterable($currentArray))) {
				return $array;
			}

			if (!isset($key, $currentArray)) {
				$currentArray[$key] = [];
			}

			$currentArray = &$currentArray[$key];
		}

		return $array;
	}

	public static function deepEmpty(iterable $arr): bool
	{
		$rec = function ($arr) use (&$rec) {
			if (self::isEmpty($arr)) {
				return true;
			}
			foreach ($arr as $value) {
				if (is_iterable($value)) {
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

	public static function mergeRecursiveDistinct(iterable $array1, iterable $array2, bool $rewrite = false): iterable
	{
		$merged = $array1;
		foreach ($array2 as $key => $value) {
			if ((is_iterable($value)) && isset($merged[$key]) && (is_iterable($merged[$key]))) {
				$merged[$key] = self::mergeRecursiveDistinct($merged[$key], $value, $rewrite);
			} else {
				if (!isset($merged[$key]) || $rewrite) {
					$merged[$key] = $value;
				}
			}
		}

		return $merged;
	}

	public static function isAssoc(iterable $array): bool
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
	 * Method recieves array of accosiated arrays with same keys
	 * Extract keys in the individual field
	 * Transfer arrays to countable with values order according to keys order
	 */
	public static function valuesStable(array $arr): array
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
	 */
	public static function arrayToJsCode(array $array): string
	{
		$rec = function ($val) use (&$rec) {
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
				if ($val[0] != '\'') return "'$val'";
			}
			if ($val === true) return 'true';
			if ($val === false) return 'false';
			if ($val === null) return 'null';
			return $val;
		};

		$result = $rec($array);
		return $result;
	}

	public static function arrayToPhpCode(array $value, int $indent = 0): string
    {
        if (empty($value)) {
            return '[]';
        }

        if (!is_array($value)) {
            return "'" .  addslashes($value) . "'";
        }

        $out = '';
        $tab = '    ';
        $margin = str_repeat($tab, $indent++);

        $out .= '[' . PHP_EOL;
        foreach ($value as $key => $row) {
            $out .= $margin . $tab;
            if (is_numeric($key)) {
                $out .= $key . ' => ';
            } else {
                $out .= "'" . $key . "' => ";
            }

            if (is_array($row)) {
                $out .= self::arrayToPhpCode($row, $indent);
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
