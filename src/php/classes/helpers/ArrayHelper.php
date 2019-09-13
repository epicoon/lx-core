<?php

namespace lx;

class ArrayHelper {
	/**
	 *
	 * */
	public static function map($array, $field) {
		$result = [];

		foreach ($array as $value) {
			if (!is_array($value) || !array_key_exists($field, $value)) continue;
			$result[$value[$field]] = $value;
		}

		return $result;
	}

	/**
	 *
	 * */
	public static function isAssoc($array) {
		$counter = 0;
		foreach ($array as $key => $value) {
			if ($key != $counter++) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Получает массив ассоциативных массивов с одинаковыми ключами
	 * Извлекает ключи в отдельное поле
	 * Все ассоциативные массивы переводит в перечислимые, с порядком элементов, соответствующим порядку ключей
	 * */
	public static function valuesStable($arr) {
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
	 * Преобразование php-массива в строку, которую можно вставить в js-код
	 * */
	public static function arrayToJsCode($array) {
		$rec = function($val) use (&$rec) {
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
}
