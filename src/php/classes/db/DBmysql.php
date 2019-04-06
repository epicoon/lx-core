<?php

namespace lx;

class DBmysql extends DB {
	/**
	 * Соединение, выбор базы данных
	 * */
	public function connect() {
		if ($this->connection !== null) return;
		$res = mysqli_connect($this->hostname, $this->username, $this->password);
		if (!$res) return false;
		mysqli_select_db($res, $this->dbName);
		$this->connection = $res;
		$this->query('set names \'utf8\'');
		$this->query('set character set \'utf8\'');
		return true;
	}

	/**
	 * Закрытие соединения
	 * */
	public function close() {
		if ($this->connection === null) return;
		mysqli_close($this->connection);
		$this->connection = null;
	}

	/**
	 * Запрос строкой с SQL-кодом
	 * */
	public function query($query) {
		if (substr($query, 0, 6) == 'SELECT') return $this->query($query);

		$result;
		$res = mysqli_query($this->connection, $query);
		if ($res === false) return false;

		if (substr($query, 0, 6) == 'INSERT') {
			$lastId = mysqli_query($this->connection, 'SELECT LAST_INSERT_ID();');
			$result = mysqli_fetch_array($lastId)[0];
		} else {
			$result = 'done';
		}

		return $result;
	}

	/**
	 * Обязательно SELECT-запрос
	 * */
	public function select($query, $selectType = DB::SELECT_TYPE_MAP) {
		$res = mysqli_query($this->connection, $query);
		if ($res === false) return false;

		$arr = [];
		/*
		необязательный параметр принимает значение константы, которая указывает на тип массива, в который требуется поместить данные. Возможные значения параметра: MYSQLI_ASSOC, MYSQLI_NUM или MYSQLI_BOTH
		*/
		while ($row = mysqli_fetch_array($res))
			$arr[] = $this->applySelectType($row, $selectType);
		return $arr;
	}

	/**
	 * Обязательно INSERT-запрос
	 * */
	public function insert($query, $returnId=true) {
		$res = mysqli_query($this->connection, $query);
		if (!$returnId) return $res;

		if ($res === false) return false;
		$lastId = mysqli_query($this->connection, 'SELECT LAST_INSERT_ID();');
		return mysqli_fetch_array($lastId)[0];
	}

	/**
	 * Массовый апдейт
	 * */
	public function massUpdate($table, $rows) {
		//todo

		/*
		Пример чтобы сделать массовый апдейт для mysql
		UPDATE `table` SET `uid` = CASE
		    WHEN id = 1 THEN 2952
		    WHEN id = 2 THEN 4925
		    WHEN id = 3 THEN 1592
		    ELSE `uid`
		    END
		WHERE id  in (1,2,3)
		*/
	}

	/**
	 * Проверяет существование таблицы
	 * */
	public function tableExists($name) {
		$res = $this->select("SHOW TABLES FROM {$this->dbName} LIKE '$name'");
		return !empty($res);
	}

	/**
	 * Схема таблицы
	 * */
	public function tableSchema($name, $fields=null) {
		$fieldsString = $fields;
		if ($fields == self::SHORT_SCHEMA) $fieldsString = 'column_name,column_default,is_nullable,data_type,character_maximum_length,column_key';
		else if (is_array($fields)) $fieldsString = implode(',', $fields);
		else if ($fields === null) $fieldsString = '*';

		$res = $this->select("SELECT $fieldsString FROM information_schema.columns WHERE table_name='$name'");
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

			if (isset($item['column_default'])) $data['default'] = $item['column_default'];

			if ($item['column_key'] == 'PRI') {
				$data['type'] = 'pk';
			}

			$data['notNull'] = $item['is_nullable'] == 'NO';

			if (!isset($data['type'])) {
				if (preg_match('/^varchar/', $item['data_type'])) $data['type'] = 'string';
				else if ($item['data_type'] == 'int') $data['type'] = 'integer';
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

	}

	/**
	 *
	 * */
	public function tableRenameColumn($tableName, $oldName, $newName) {
		
	}

	/**
	 *
	 * */
	public function tableAddColumn($tableName, $name, $definition) {

	}

	/**
	 *
	 * */
	public function tableDropColumn($tableName, $name) {

	}

	/**
	 * Дефиниция для таймштампа без временной зоны
	 * */
	public function timestamp($conf=[]) {
		$conf['type'] = 'timestamp';
		return new DbColumnDefinition($conf);
	}

	/**
	 * Преобразует объект дефиниции поля в строку
	 * */
	public function definitionToString($definition) {
		$str = $definition->type;
		if ($definition->size !== null) $str .= "({$definition->size})";
		if ($definition->notNull) $str .= ' not null';
		if ($definition->isPK) $str .= 'INT NOT NULL AUTO_INCREMENT, PRIMARY KEY (#key#)';
		if ($definition->default !== null) $str .= " default {$definition->default}";
		return $str;
	}
}
