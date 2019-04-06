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
}
