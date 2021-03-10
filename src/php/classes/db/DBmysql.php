<?php

namespace lx;

//TODO сотояние кода устарело

class DBmysql extends DB {
	/**
	 * Имя таблицы с учетом схемы
	 * */
	public function tableName($name) {
		return str_replace('.', '__', $name);
	}

    public function getCreateTableQuery($schema) {
	    //TODO - раньше были аргументы ($name, $columns)

		$query = '';
		if (preg_match('/\./', $name)) {
			$name = $this->tableName($name);
		}

		$query .= "CREATE TABLE $name (";
		$cols = [];
		foreach ($columns as $colName => $definition) {
			if (is_array($definition)) {
				$definition = new DbColumnDefinition($definition);
			}

			$str = $this->definitionToString($definition);
			$str = str_replace('#key#', $colName, $str);
			$cols[] = "$colName $str";
		}
		$cols = implode(', ', $cols);
		$cols = str_replace('#pkey#', $name, $cols);
		$query .= "$cols);CHARACTER SET utf8 COLLATE utf8_general_ci;";
		return $query;
	}

	/**
	 * Запрос строкой с SQL-кодом
	 * @param $query
	 * */
	public function query($query) {
		$this->error = null;

		if (preg_match('/^\s*SELECT/', $query)) {
			return $this->select($query);
		}

		$result;
		$res = mysqli_query($this->connection, $query);
		if ($res === false) {
			$this->error = mysqli_error($this->connection);
			return false;
		}

		if (preg_match('/^\s*INSERT/', $query)) {
			$lastId = mysqli_query($this->connection, 'SELECT LAST_INSERT_ID();');
			$result = mysqli_fetch_array($lastId)[0];
		} else {
			$result = 'done';
		}

		return $result;
	}

	/**
	 * SELECT-запрос
	 * @param $query
	 * */
	public function select($query, $selectType = DB::SELECT_TYPE_MAP) {
		$this->error = null;

		$res = mysqli_query($this->connection, $query);
		if ($res === false) {
			$this->error = mysqli_error($this->connection);
			return false;
		}

		$arr = [];
		while ($row = mysqli_fetch_array($res)) {
			$arr[] = $this->applySelectType($row, $selectType);
		}

		return $arr;
	}

	/**
	 * Обязательно INSERT-запрос
	 * */
	public function insert($query, $returnId=true) {
		$this->error = null;

		$res = mysqli_query($this->connection, $query);
		if (!$returnId) return $res;

		if ($res === false) {
			$this->error = mysqli_error($this->connection);
			return false;
		}

		$lastId = mysqli_query($this->connection, 'SELECT LAST_INSERT_ID();');
		return mysqli_fetch_array($lastId)[0];
	}

	/**
	 * Массовый апдейт
	 * */
	public function massUpdate($tableName, $rows) {
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
		$name = $this->tableName($name);
		$res = $this->select("SHOW TABLES FROM {$this->getName()} LIKE '$name'");
		return !empty($res);
	}

	/**
	 * Схема таблицы
	 * */
	public function tableSchema($name, $fields=null) {
		$name = $this->tableName($name);
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

    public function getAddColumnQuery($schema, $fieldName) {
	    //TODO
		/*
		Для внешнего ключа

		ALTER TABLE users ADD grade_id SMALLINT UNSIGNED NOT NULL DEFAULT 0;
		ALTER TABLE users ADD CONSTRAINT fk_grade_id FOREIGN KEY (grade_id) REFERENCES grades(id);

		ALTER TABLE my_table DROP FOREIGN KEY fk_name;
		ALTER TABLE my_table DROP COLUMN my_column;

		
		Вроде как про "по поводу посмотреть есть ли внешний ключ":
		
		1) Попроще (показывает CONSTRAINT_NAME, REFERENCED_TABLE_NAME и REFERENCED_COLUMN_NAME для полей таблицы, если они есть):
		use INFORMATION_SCHEMA;
		select 
		  TABLE_NAME,COLUMN_NAME,CONSTRAINT_NAME,REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME 
		from 
		  KEY_COLUMN_USAGE
		where 
		  TABLE_SCHEMA = "ИМЯ_БАЗЫ" 
		and 
		  TABLE_NAME = "ИМЯ_ТАБЛИЦЫ" 
		and 
		  REFERENCED_COLUMN_NAME is not NULL;

		2) Понавороченней, показывает ВСЁ:
		use INFORMATION_SCHEMA;
		SELECT 
		  cols.TABLE_NAME, cols.COLUMN_NAME, cols.ORDINAL_POSITION,
		  cols.COLUMN_DEFAULT, cols.IS_NULLABLE, cols.DATA_TYPE,
		  cols.CHARACTER_MAXIMUM_LENGTH, cols.CHARACTER_OCTET_LENGTH,
		  cols.NUMERIC_PRECISION, cols.NUMERIC_SCALE,
		  cols.COLUMN_TYPE, cols.COLUMN_KEY, cols.EXTRA,
		  cols.COLUMN_COMMENT, refs.REFERENCED_TABLE_NAME,
		  refs.REFERENCED_COLUMN_NAME,
		  cRefs.UPDATE_RULE, cRefs.DELETE_RULE,
		  links.TABLE_NAME, links.COLUMN_NAME,
		  cLinks.UPDATE_RULE, cLinks.DELETE_RULE
		FROM 
		  `COLUMNS` as cols
		LEFT JOIN `KEY_COLUMN_USAGE` AS refs ON 
		  refs.TABLE_SCHEMA=cols.TABLE_SCHEMA
		  AND refs.REFERENCED_TABLE_SCHEMA=cols.TABLE_SCHEMA
		  AND refs.TABLE_NAME=cols.TABLE_NAME
		  AND refs.COLUMN_NAME=cols.COLUMN_NAME
		LEFT JOIN REFERENTIAL_CONSTRAINTS AS cRefs ON 
		  cRefs.CONSTRAINT_SCHEMA=cols.TABLE_SCHEMA
		  AND cRefs.CONSTRAINT_NAME=refs.CONSTRAINT_NAME
		LEFT JOIN `KEY_COLUMN_USAGE` AS links ON 
		  links.TABLE_SCHEMA=cols.TABLE_SCHEMA
		  AND links.REFERENCED_TABLE_SCHEMA=cols.TABLE_SCHEMA
		  AND links.REFERENCED_TABLE_NAME=cols.TABLE_NAME
		  AND links.REFERENCED_COLUMN_NAME=cols.COLUMN_NAME
		LEFT JOIN REFERENTIAL_CONSTRAINTS AS cLinks ON 
		  cLinks.CONSTRAINT_SCHEMA=cols.TABLE_SCHEMA
		  AND cLinks.CONSTRAINT_NAME=links.CONSTRAINT_NAME
		WHERE 
		  cols.TABLE_SCHEMA="ИМЯ_БАЗЫ"
		AND 
		  cols.TABLE_NAME="ИМЯ_ТАБЛИЦЫ";
		*/
	}

	/**
	 * Дефиниция для таймштампа без временной зоны
	 * */
	public function timestamp($conf=[]) {
		$conf['type'] = 'timestamp';
		return new DbColumnDefinition($conf);
	}
}
