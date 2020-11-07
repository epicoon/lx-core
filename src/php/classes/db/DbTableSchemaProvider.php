<?php

namespace lx;

/**
 * @deprecated
 * Class DbTableSchemaProvider
 * @package lx
 */
class DbTableSchemaProvider {
	private static $map = [];

	static function get($table) {
		$tableKey = $table->getDb()->getName() . '.' . $table->getName();

		if (!array_key_exists($tableKey, self::$map)) {
			$schema = $table->getDb()->tableSchema($table->getName(), DB::SHORT_SCHEMA);
			$pk = null;
			$fields = [];
			$defaults = [];
			$types = [];
			$notNull = [];
			foreach ($schema as $key => $value) {
				$fields[] = $key;
				
				// Определение первичного ключа
				if ($value['type'] == 'pk') {
				// if (array_key_exists('default', $value) && $value['default'] == '@PK') {
					$pk = $key;
				// Выбрасываем первичный ключ из проверки на null, т.к. технически он может зануляться в режиме DbRecord
				} else {
					if ($value['notNull'] === true) $notNull[] = $key;
				}

				// Определение дефолтных значений
				$default = null;
				if (array_key_exists('default', $value) && $value['default'] != '@PK') {
					if ($value['type'] == DB::TYPE_BOOLEAN) {
						if ($value['default'] == 'false') $default = false;
						elseif ($value['default'] == 'true') $default = true;
						else $default = $value['default'];
					} else {
						$default = $value['default'];
					}
				}
				$defaults[$key] = $default;

				// Определение типов
				//todo!!!!!!!!!!!!! жесткая привязка первичного ключа к одному полю типа int - также смотреть где схема строится. И схему моделей.
				$types[$key] = $value['type'] == 'pk' ? 'integer' : $value['type'];
			}

			self::$map[$tableKey] = new DbSchema([
				'pk' => $pk,
				'fields' => $fields,
				'types' => $types,
				'defaults' => $defaults,
				'notNull' => $notNull,
			]);
		}

		return self::$map[$tableKey];
	}
}
