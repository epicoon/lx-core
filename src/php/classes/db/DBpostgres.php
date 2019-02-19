<?php

namespace lx;

class DBpostgres extends DB {
	/**
	 * Соединение, выбор базы данных
	 * */
	public function connect() {
		if ($this->connect !== null) return true;
		if (!function_exists('\pg_connect')) return false;
		$res = \pg_connect("host={$this->hostname} dbname={$this->dbName} user={$this->username} password={$this->password}");
		if (!$res) return false;
		$this->connect = $res;
		return true;
	}

	/**
	 * Закрытие соединения
	 * */
	public function close() {
		if ($this->connect === null) return;
		pg_close($this->connect);
		$this->connect = null;
	}

	/**
	 * Запрос строкой с SQL-кодом
	 * */
	public function query($query) {
		if (substr($query, 0, 6) == 'SELECT') return $this->select($query);

		if (substr($query, 0, 6) == 'INSERT') {
			$query .= ' RETURNING id';
			$res = pg_query($query);
			$arr = [];
			while ($row = pg_fetch_array($res)) $arr[] = $row[0];
			pg_free_result($res);
			if (count($arr) == 1) return $arr[0];
			return $arr;
		}

		$result;
		$res = pg_query($query);
		if ($res === false) return false;
		$result = 'done';

		pg_free_result($res);
		return $result;
	}

	/**
	 * Обязательно SELECT-запрос
	 * */
	public function select($query, $selectType = DB::SELECT_TYPE_MAP) {
		$res = pg_query($query);
		if ($res === false) return false;

		$arr = [];
		while ($row = pg_fetch_array($res)) {
			$arr[] = $this->applySelectType($row, $selectType);
		}
		pg_free_result($res);
		return $arr;
	}

	/**
	 * Добавление новой строки
	 * */
	public function insert($query, $returnId=true) {
		if (!$returnId) return pg_query($query);

		$query .= ' RETURNING id';
		$res = pg_query($query);
		$arr = [];
		while ($row = pg_fetch_array($res)) $arr[] = $row[0];
		pg_free_result($res);
		if (count($arr) == 1) return $arr[0];
		return $arr;
	}

	/**
	 * Массовый апдейт
	 * */
	public function massUpdate($table, $rows) {
		/*
		--Такой запрос работает:
		UPDATE table_name SET
			f_int = t.f_int,
			f_string = t.f_string
		FROM (
			SELECT
				unnest(array[1, 2]) as id,
				unnest(array[1, null::integer]) as f_int,
				unnest(array[null, 'aaa']) as f_string
		) as t
		WHERE table_name.id = t.id;
		*/

		//todo - схема кэшируется. Подумать оставять так или нет
		$schema = $table->schema();
		$pk = $schema['pk'];
		$fields = array_keys($schema['types']);
		$set = [];
		$values = [];
		foreach ($fields as $field) {
			$vals = [];
			// foreach ($records as $record) {
			foreach ($rows as $row) {
				$val = DB::valueForQuery($row[$field]);
				// $val = DB::valueForQuery($record->$field);
				if ($val == 'NULL' && $schema['types'][$field] != DB::TYPE_STRING) {
					$val .= '::' . $schema['types'][$field];
				}
				$vals[] = $val;
			}
			$vals = implode(', ', $vals);
			$values[] = "unnest(array[$vals]) as $field";

			if ($field == $pk) continue;
			$set[] = "$field = t.$field";
		}
		$set = implode(', ', $set);
		$values = implode(', ', $values);

		$query = "UPDATE {$table->getName()} SET $set FROM (SELECT $values) as t WHERE {$table->getName()}.$pk = t.$pk";
		$result;
		$res = pg_query($query);
		if ($res === false) return false;
		$result = 'done';

		pg_free_result($res);
		return $result;
	}

	/**
	 * Проверяет существование таблицы
	 * */
	public function tableExists($name) {
		$res = $this->select("SELECT * FROM pg_tables where tablename='$name'");
		return !empty($res);
	}

	/**
	 * Схема таблицы
	 * */
	public function tableSchema($name, $fields=null) {
		$fieldsString = $fields;
		if ($fields == self::SHORT_SCHEMA) $fieldsString = 'column_name,column_default,is_nullable,data_type,character_maximum_length';
		elseif (is_string($fields)) $fieldsString = $fields;
		elseif (is_array($fields)) $fieldsString = implode(',', $fields);
		elseif ($fields === null) $fieldsString = '*';

		$res = $this->select("SELECT $fieldsString FROM information_schema.columns WHERE table_name='$name'");
		if (is_string($fields)) {
			$arr = [];
			foreach ($res as $value) $arr[] = $value[$fields];
			return $arr;
		}
		if ($fields != self::SHORT_SCHEMA) return $res;

		/*
		Для короткой схемы вернёт данные в формате:
		[
			[
				'name' - всегда
				'type' - всегда
				'notNull' - всегда, булево значение
				'default' - если есть
				'size' - если есть
			],
			...
		]
		*/
		$result = [];
		foreach ($res as $item) {
			$data = [];
			$name = $item['column_name'];
			if (isset($item['column_default'])) {
				if (preg_match('/^nextval\(.*seq/', $item['column_default'])) {
					$data['type'] = 'pk';
				} else {
					$data['default'] = $item['column_default'];
				}
			}
			$data['notNull'] = $item['is_nullable'] == 'NO';
			if (!isset($data['type'])) {
				if (preg_match('/^character/', $item['data_type'])) $data['type'] = 'string';
				else $data['type'] = $item['data_type'];
			}
			if (isset($item['character_maximum_length'])) {
				$data['size'] = $item['character_maximum_length'];
			}
			$result[$name] = $data;
		}
		return $result;
	}

	/**
	 *
	 * */
	public function renameTable($oldName, $newName) {
		$this->query("ALTER TABLE $oldName RENAME TO $newName;");
	}

	/**
	 *
	 * */
	public function tableRenameColumn($tableName, $oldName, $newName) {
		$this->query("ALTER TABLE $tableName RENAME COLUMN $oldName TO $newName;");
	}

	/**
	 *
	 * */
	public function tableAddColumn($tableName, $name, $definition) {
		$definitionString = $this->definitionToString($definition);
		$this->query("ALTER TABLE $tableName ADD COLUMN $name $definitionString;");
	}

	/**
	 *
	 * */
	public function tableDropColumn($tableName, $name) {
		$this->query("ALTER TABLE $tableName DROP COLUMN $name;");
	}

	/**
	 * Дефиниция для таймштампа без временной зоны
	 * */
	public function timestamp($conf=[]) {
		$conf['type'] = 'timestamp without time zone';
		return new DbColumnDefinition($conf);
	}

	/**
	 * Дефиниция для таймштампа с временной зоной
	 * */
	public function timestampTZ($conf=[]) {
		$conf['type'] = 'timestamp with time zone';
		return new DbColumnDefinition($conf);
	}

	/**
	 * Преобразует объект дефиниции поля в строку
	 * */
	public function definitionToString($definition) {
		$str = $definition->type;
		if ($definition->size !== null) $str .= "({$definition->size})";
		if ($definition->notNull) $str .= ' not null';
		if ($definition->isPK) $str .= 'serial not null constraint #pkey#_pkey primary key';
		if ($definition->default !== null) $str .= " default {$definition->default}";
		return $str;
	}

	/**
	 * Postgresql все подряд загружает как строки, нам надо нормально
	 * */
	public function normalizeTypes($table, $rows) {
		$schema = $table->schema();
		foreach ($rows as &$row) {
			foreach ($schema['types'] as $field => $type) {
				if (!array_key_exists($field, $row) || $row[$field] === null) continue;

				switch ($type) {
					case DB::TYPE_INTEGER:
						$row[$field] = (int)$row[$field];
						break;
					case DB::TYPE_BOOLEAN:
						if ($row[$field] == 'f') $row[$field] = false;
						elseif ($row[$field] == 't') $row[$field] = true;
						break;
				}
			}
		}
		unset($row);
		return $rows;
	}
}
