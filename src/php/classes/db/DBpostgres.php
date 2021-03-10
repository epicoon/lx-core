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
        list($schemaName, $shortTableName) = $this->splitTableName($tableName);
        $fields = $this->select("
            SELECT *
            FROM information_schema.columns
            WHERE table_schema='{$schemaName}' AND table_name='{$shortTableName}'
        ");

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

        $constraintKeys = $this->select("
			SELECT
			    tc.constraint_name,
                tc.constraint_type,
                kcu.column_name as column_name,
                ccu.table_schema,
                ccu.table_name,
                ccu.column_name as rel_column_name
			FROM information_schema.table_constraints as tc
                LEFT JOIN information_schema.key_column_usage AS kcu
                    ON tc.constraint_name = kcu.constraint_name
                    AND tc.table_schema = kcu.table_schema
			    LEFT JOIN information_schema.constraint_column_usage AS ccu
                    ON ccu.constraint_name = tc.constraint_name
                    AND ccu.table_schema = tc.table_schema
			WHERE (tc.constraint_type='FOREIGN KEY' OR tc.constraint_type='PRIMARY KEY')
				AND tc.table_schema='$schemaName'
				AND tc.table_name='$shortTableName';
		");
        foreach ($constraintKeys as $key) {
            $fieldName = $key['column_name'];
            if ($key['constraint_type'] == 'FOREIGN KEY') {
                $config['fields'][$fieldName]['fk'] = [
                    'table' => $key['table_schema'] . '.' . $key['table_name'],
                    'field' => $key['rel_column_name'],
                    'name' => $key['constraint_name'],
                ];
            } else {
                $config['fields'][$fieldName]['pk'] = true;
            }
        }

        $schema = DbTableSchema::createByConfig($this, $config);
        if ($pkName !== null) {
            $schema->setPK($pkName);
        }

        return $schema;
    }

    /**
     * @param string $tableName
     * @return array
     */
    public function getContrForeignKeysInfo($tableName)
    {
        list($schemaName, $shortTableName) = $this->splitTableName($tableName);
        return $this->select("
            SELECT
                kcu.table_schema || '.' || kcu.table_name as table,
                kcu.column_name as field,
                tc.constraint_name as name
            FROM information_schema.table_constraints as tc
                LEFT JOIN information_schema.key_column_usage AS kcu
                    ON tc.constraint_name = kcu.constraint_name
                    AND tc.table_schema = kcu.table_schema
                LEFT JOIN information_schema.constraint_column_usage AS ccu
                    ON ccu.constraint_name = tc.constraint_name
                    AND ccu.table_schema = tc.table_schema
            WHERE tc.constraint_type='FOREIGN KEY'
                AND ccu.table_schema='$schemaName'
                AND ccu.table_name='$shortTableName'
                AND ccu.column_name='id';
        ");//TODO AND ccu.colimn_name='id' захардкожена завязка на конкретный первичный ключ
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

        if (!empty($pkFields)) {
            //TODO сделано под одиночный целочисленный первичный ключ
            $pKeyName = str_replace('.', '_', $name) . '_pkey';
            $cols = [
                "{$pkFields[0]->getName()} serial not null constraint $pKeyName primary key"
            ];
        }

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

    public function getAddForeignKeyQuery(
        string $table,
        string $field,
        string $relTable,
        string $relField,
        ?string $constraintName = null
    ): string
    {
        if ($constraintName === null) {
            $tableKey = str_replace('.', '_', $table);
            $constraintName = "fk__{$tableKey}__$field";
        }
        
        //TODO ON DELETE|UPDATE CASCADE|RESTRICT
        return "ALTER TABLE {$table} ADD CONSTRAINT $constraintName FOREIGN KEY ({$field})
			REFERENCES {$relTable}({$relField});
		";
    }

    public function getDropForeignKeyQuery(
        string $table, string $field,
        ?string $constraintName = null
    ): string
    {
        if ($constraintName === null) {
            $tableKey = str_replace('.', '_', $table);
            $constraintName = "fk__{$tableKey}__$field";
        }
        
        return"ALTER TABLE $table DROP CONSTRAINT $constraintName;";
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
        sort($arr);
		return $arr;
	}

	/**
	 * Массовый апдейт
     * @return bool
	 */
	public function massUpdate($tableName, $rows)
    {
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

        $schema = $this->getTableSchema($tableName);

        $set = [];
        $values = [];
        foreach ($schema->getFields() as $fieldName => $field) {
            $vals = [];
            foreach ($rows as $row) {
                $val = DB::valueForQuery($row[$fieldName] ?? null);
                if ($val == 'NULL' && $field->getType() != DbTableField::TYPE_STRING) {
                    $val .= '::' . $field->getType();
                }
                $vals[] = $val;
            }
            $vals = implode(', ', $vals);
            $values[] = "unnest(array[{$vals}]) as {$fieldName}";

            if ($field->isPk()) {
                continue;
            }
            
            $set[] = "{$fieldName} = t.{$fieldName}";
        }
        $set = implode(', ', $set);
        $values = implode(', ', $values);

        $query = "UPDATE {$tableName} SET $set FROM (SELECT $values) as t WHERE {$tableName}.id = t.id";
        //TODO первичный ключ захардкожен id

        $res = pg_query($this->connection, $query);
        if ($res === false) {
            $this->error = pg_last_error($this->connection);
            return false;
        }

        pg_free_result($res);
        return true;
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

	//TODO
//    /**
//     * ?????? надо что-то с ними решать
//     * Дефиниция для таймштампа без временной зоны
//     * */
//    public function timestamp($conf=[]) {
//        $conf['type'] = 'timestamp without time zone';
//        return new DbColumnDefinition($conf);
//    }
//
//    /**
//     * ?????? надо что-то с ними решать
//     * Дефиниция для таймштампа с временной зоной
//     * */
//    public function timestampTZ($conf=[]) {
//        $conf['type'] = 'timestamp with time zone';
//        return new DbColumnDefinition($conf);
//    }

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
     * @return array [schemaName, shortTableName]
     */	
	private function splitTableName($tableName)
    {
        if (preg_match('/\./', $tableName)) {
            $arr = explode('.', $tableName);
            $schemaName = $arr[0];
            $shortTableName = $arr[1];
        } else {
            $schemaName = 'public';
            $shortTableName = $tableName;
        }

        return [$schemaName, $shortTableName];
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
