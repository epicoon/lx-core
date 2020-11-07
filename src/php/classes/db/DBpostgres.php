<?php

namespace lx;

/**
 * Class DBpostgres
 * @package lx
 */
class DBpostgres extends DB
{
    /**
     * @param string $name
     * @return string
     */
    public function tableName($name)
    {
        return $name;
    }

    /**
     * @param string $tableName
     * @return DbTableSchema|null
     */
    public function getTableSchema($tableName)
    {
        if (preg_match('/\./', $tableName)) {
            $arr = explode('.', $tableName);
            $fields = $this->select("
                SELECT * FROM information_schema.columns WHERE table_schema='{$arr[0]}' AND table_name='{$arr[1]}'
            ");
        } else {
            $fields = $this->select("
                SELECT * FROM information_schema.columns WHERE table_name='$tableName'
            ");
        }

        if (empty($fields)) {
            return null;
        }

        $pkName = null;
        $fieldDefinitions = [];
        foreach ($fields as $field) {
            $fieldName = $field['column_name'];
            $definition = ['name' => $fieldName];

            //TODO конвертить какие-то еще типы? Как минимум всякие smallint с инициализацией size
            $type = $field['data_type'];
            if (preg_match('/^character/', $type)) {
                $type = DbTableField::TYPE_STRING;
            }
            $definition['type'] = $type;

            $size = $field['character_maximum_length'];
            if ($size !== null) {
                $definition['size'] = (int)$size;
            }

            $definition['nullable'] = ($field['is_nullable'] == 'YES');

            $default = $field['column_default'];
            if ($default !== null) {
                if (preg_match('/^nextval\(.*seq/', $default)) {
                    $pkName = $fieldName;
                } else {
                    if (preg_match('/\'(.+?)\'::character/', $default, $matches)) {
                        $default = $matches[1];
                    }

                    $definition['default'] = $default;
                }
            }

            $fieldDefinitions[$fieldName] = $definition;
        }

        $config = [
            'name' => $tableName,
            'fields' => $fieldDefinitions,
        ];
        $schema = DbTableSchema::createByConfig($this, $config);
        if ($pkName !== null) {
            $schema->setPK($pkName);
        }

        return $schema;
    }

    /**
     * @param DbTableSchema $schema
     * @return string
     */
    public function getCreateTableQuery($schema)
    {
        $name = $schema->getName();
        $query = '';
        if (preg_match('/\./', $name)) {
            $arr = explode('.', $name);
            $query = "CREATE SCHEMA IF NOT EXISTS {$arr[0]};";
        }

        $query .= "CREATE TABLE IF NOT EXISTS $name (";

        $fields = $schema->getFields();
        $pkNames = $schema->getPKNames();
        $pkFields = [];
        foreach ($pkNames as $pkName) {
            $pkFields[] = $fields[$pkName];
            unset($fields[$pkName]);
        }

        //TODO сделано под одиночный целочисленный первичный ключ
        $pKeyName = str_replace('.', '_', $name) . '_pkey';
        $cols = [
            "{$pkFields[0]->getName()} serial not null constraint $pKeyName primary key"
        ];

        foreach ($fields as $fieldName => $fieldDefinition) {
            $str = $this->fieldToString($fieldDefinition);
            $str = str_replace('#key#', $fieldName, $str);
            $cols[] = "$fieldName $str";
        }
        $cols = implode(', ', $cols);
        $cols = str_replace('#pkey#', str_replace('.', '_', $name), $cols);
        $query .= "$cols);";
        return $query;
    }

    /**
     * @param DbTableSchema $schema
     * @param string $fieldName
     * @return string
     */
    public function getAddColumnQuery($schema, $fieldName)
    {
        $field = $schema->getField($fieldName);
        $definition = $this->fieldToString($field);
        //TODO if ($field->isFk())

        return "ALTER TABLE {$schema->getName()} ADD COLUMN $fieldName $definition;";
    }

    /**
     * @param DbTableSchema $schema
     * @param string $fieldName
     * @return string
     */
    public function getDelColumnQuery($schema, $fieldName)
    {
        //TODO
//        if ($this->checkForeignKeyExists($tableName, $columnName)) {
//            $table = str_replace('.','_', $tableName);
//            $fkName = "fk__{$table}__$columnName";
//            $this->query("ALTER TABLE $tableName DROP CONSTRAINT $fkName;");
//        }

        return "ALTER TABLE {$schema->getName()} DROP COLUMN $fieldName;";
    }

    /**
     * @param DbTableSchema $schema
     * @param string $fieldName
     * @return string
     */
    public function getChangeColumnQuery($schema, $fieldName)
    {
        $tableName = $schema->getName();
        $field = $schema->getField($fieldName);

        $type = $field->getTypeDefinition();
        $queries = [
            "ALTER TABLE $tableName ALTER COLUMN $fieldName TYPE $type;"
        ];

        if ($field->isNullable()) {
            $queries[] = "ALTER TABLE $tableName ALTER COLUMN $fieldName DROP NOT NULL;";
        } else {
            $queries[] = "ALTER TABLE $tableName ALTER COLUMN $fieldName SET NOT NULL;";
        }

        $default = $field->getDefault();
        if ($default === null) {
            $queries[] = "ALTER TABLE $tableName ALTER COLUMN $fieldName DROP DEFAULT;";
        } else {
            $default = DB::valueForQuery($default);
            $queries[] = "ALTER TABLE $tableName ALTER COLUMN $fieldName SET DEFAULT $default;";
        }


        //TODO if ($field->isFk())


        $query = implode('', $queries);
        return $query;
    }

    /**
     * @param DbTableSchema $schema
     * @param string $oldFieldName
     * @param string $newFieldName
     * @return string
     */
    public function getRenameColumnQuery($schema, $oldFieldName, $newFieldName)
    {
        return "ALTER TABLE {$schema->getName()} RENAME COLUMN $oldFieldName TO $newFieldName;";
    }

	/**
	 * Запрос строкой с SQL-кодом
	 * */
	public function query($query) {
		if (preg_match('/^\s*SELECT/', $query)) {
			return $this->select($query);
		}

		if (preg_match('/^\s*INSERT/', $query)) {
			$query .= ' RETURNING id';
			$res = pg_query($this->connection, $query);
			if ($res === false) {
				$this->error = pg_last_error($this->connection);
				return false;
			}

			$arr = [];
			while ($row = pg_fetch_array($res)) $arr[] = $row[0];

			pg_free_result($res);
			if (count($arr) == 1) {
				return $arr[0];
			}

			return $arr;
		}

		$result;
		$res = pg_query($this->connection, $query);
		if ($res === false) {
			$this->error = pg_last_error($this->connection);
			return false;
		}

		$result = 'done';

		pg_free_result($res);
		return $result;
	}

	/**
	 * Обязательно SELECT-запрос
	 * */
	public function select($query, $selectType = DB::SELECT_TYPE_MAP) {
		$res = pg_query($this->connection, $query);
		if ($res === false) {
			$this->error = pg_last_error($this->connection);
			return false;
		}

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
		if (!$returnId) return pg_query($this->connection, $query);

		$query .= ' RETURNING id';
		$res = pg_query($this->connection, $query);
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
		$pk = $schema->getPk();
		$fields = $schema->getFields();
		$set = [];
		$values = [];
		foreach ($fields as $field) {
			$vals = [];
			foreach ($rows as $row) {
				$val = DB::valueForQuery($row[$field]);
				if ($val == 'NULL' && $schema->getType($field) != DB::TYPE_STRING) {
					$val .= '::' . $schema->getType($field);
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
		$res = pg_query($this->connection, $query);
		if ($res === false) {
			$this->error = pg_last_error($this->connection);
			return false;
		}

		$result = 'done';

		pg_free_result($res);
		return $result;
	}

	/**
	 * Проверяет существование таблицы
	 * */
	public function tableExists($name) {
		if (preg_match('/\./', $name)) {
			$arr = explode('.', $name);
			$res = $this->select("
                SELECT * FROM pg_tables where schemaname='{$arr[0]}' AND tablename='{$arr[1]}'
            ");
		} else {
			$res = $this->select("SELECT * FROM pg_tables where tablename='$name'");
		}

		return !empty($res);
	}

    /**
     * @param string $oldName
     * @param string $newName
     */
    public function renameTable($oldName, $newName)
    {
        $this->query("ALTER TABLE $oldName RENAME TO $newName;");
    }






















    /**
     * @deprecated
     * @param string $tableName
     * @param string $oldName
     * @param string $newName
     */
    public function renameTableColumn($tableName, $oldName, $newName)
    {
        $this->query("ALTER TABLE $tableName RENAME COLUMN $oldName TO $newName;");
    }

    /**
     * @deprecated
     * @param $tableName
     * @param $columnName
     * @param $definition
     * @return bool
     */
    public function addTableColumn($tableName, $columnName, $definition)
    {
        $definitionString = $this->definitionToString($definition);
        $this->query("ALTER TABLE $tableName ADD COLUMN $columnName $definitionString;");

        if ($definition->isFK) {
            return $this->addForeignKeyProcess($tableName, $columnName, $definition);
        }

        return true;
    }

    /**
     * @deprecated
     * @param $tableName
     * @param $columnName
     */
    public function dropTableColumn($tableName, $columnName)
    {
        if ($this->checkForeignKeyExists($tableName, $columnName)) {
            $table = str_replace('.','_', $tableName);
            $fkName = "fk__{$table}__$columnName";
            $this->query("ALTER TABLE $tableName DROP CONSTRAINT $fkName;");
        }

        $this->query("ALTER TABLE $tableName DROP COLUMN $columnName;");
    }

    /**
     * @deprecated
     * Формирует запрос для создания новой таблицы
     * */
    public function newTableQuery($name, $columns)
    {
        $query = '';
        if (preg_match('/\./', $name)) {
            $arr = explode('.', $name);
            $query = "CREATE SCHEMA IF NOT EXISTS {$arr[0]};";
        }

        $query .= "CREATE TABLE IF NOT EXISTS $name (";
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
        $cols = str_replace('#pkey#', str_replace('.', '_', $name), $cols);
        $query .= "$cols);";
        return $query;
    }

	/**
     * @deprecated
	 * Схема таблицы
	 * */
	public function tableSchema($name, $fields=null) {
		$fieldsString = $fields;
		if ($fields == self::SHORT_SCHEMA) $fieldsString = 'column_name,column_default,is_nullable,data_type,character_maximum_length';
		elseif (is_string($fields)) $fieldsString = $fields;
		elseif (is_array($fields)) $fieldsString = implode(',', $fields);
		elseif ($fields === null) $fieldsString = '*';

		if (preg_match('/\./', $name)) {
			$arr = explode('.', $name);
			$res = $this->select("SELECT $fieldsString FROM information_schema.columns WHERE table_schema='{$arr[0]}' AND table_name='{$arr[1]}'");
		} else {
			$res = $this->select("SELECT $fieldsString FROM information_schema.columns WHERE table_name='$name'");
		}
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
			$data['notNull'] = ($item['is_nullable'] == 'NO');
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
     * @deprecated
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
     * ?????? надо что-то с ними решать
     * Дефиниция для таймштампа без временной зоны
     * */
    public function timestamp($conf=[]) {
        $conf['type'] = 'timestamp without time zone';
        return new DbColumnDefinition($conf);
    }

    /**
     * ?????? надо что-то с ними решать
     * Дефиниция для таймштампа с временной зоной
     * */
    public function timestampTZ($conf=[]) {
        $conf['type'] = 'timestamp with time zone';
        return new DbColumnDefinition($conf);
    }










	public function addForeignKey($config)
	{
		if (is_string($config)) {
			preg_match_all('/\b[\w\d_]+?\b/', $config, $matches);
			if (count($matches[0]) < 4) {
				$this->error = 'Wrong data for foreign key: "'. $config .'"';
				return false;
			}
			$config = $matches[0];
		}

		$definition = $this->foreignKeyDefinition($config);
		$schema = $this->table($definition->isFK['table'])->schema();
		if ($schema->hasField($definition->isFK['field'])) {
			return $this->addForeignKeyProcess(
				$definition->isFK['table'],
				$definition->isFK['field'],
				$definition
			);
		}

		return $this->addTableColumn(
			$definition->isFK['table'],
			$definition->isFK['field'],
			$definition
		);
	}

	public function checkForeignKeyExists($tableName, $name)
	{
		if (preg_match('/\./', $tableName)) {
			list($schema, $table) = explode('.', $tableName);
			$fkName = "fk__{$schema}_{$table}__$name";
		} else {
			$schema = 'public';
			$table = $tableName;
			$fkName = "fk__{$table}__$name";
		}

		$fk = $this->select("
			SELECT * FROM information_schema.table_constraints
			WHERE constraint_type='FOREIGN KEY'
				AND table_schema='$schema'
				AND table_name='$table'
				AND constraint_name='$fkName';
		");

		return !empty($fk);
	}

	public function dropForeignKey($config)
	{
		if (is_string($config)) {
			preg_match_all('/\b[\w\d_]+?\b/', $config, $matches);
			if (count($matches[0]) != 2) {
				$this->error = 'Wrong data for drop foreign key: "'. $config .'"';
				return false;
			}
			$config = $matches[0];
		}

		$definition = $this->foreignKeyDefinition($config);
		$this->dropTableColumn(
			$definition->isFK['table'],
			$definition->isFK['field']
		);

		return true;
	}











	/**
	 * Postgresql все подряд загружает как строки, нам надо нормально
	 * */
	public function normalizeTypes($table, $rows) {
		$schema = $table->schema();
		foreach ($rows as &$row) {
			foreach ($schema->getTypes() as $field => $type) {
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

    /**
     * @param string $tableName
     * @param string $name
     * @param $definition
     * @return bool
     */
	private function addForeignKeyProcess($tableName, $name, $definition)
    {
		if ($this->checkForeignKeyExists($tableName, $name)) {
			$this->error = 'Foreign key for "'. $tableName . '.' . $name .'" already exists';
			return false;
		}

		$table = str_replace('.','_', $tableName);
		$fkName = "fk__{$table}__$name";

		//TODO ON DELETE|UPDATE CASCADE|RESTRICT
		$this->query("
			ALTER TABLE $tableName ADD CONSTRAINT $fkName FOREIGN KEY ($name)
			REFERENCES {$definition->isFK['refTable']}({$definition->isFK['refField']});
		");

		return true;
	}

    /**
     * @param DbTableField $field
     * @return string
     */
    private function fieldToString($field)
    {
        $type = $field->getType();
        //TODO приведение типов?

        $result = $type;
        $size = $field->getSize();
        if ($size !== null) {
            $result .= "({$size})";
        }

        if (!$field->isNullable()) {
            $result .= ' not null';
        }

        $default = $field->getDefault();
        if ($default !== null) {
            $default = DB::valueForQuery($default);
            $result .= " default {$default}";
        }

        return $result;
    }
}
