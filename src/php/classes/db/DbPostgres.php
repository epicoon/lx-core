<?php

namespace lx;

class DbPostgres extends DbConnection
{
    /** @var array<array<DbTableSchema>> */
    private array $schemas = [];
    
    public function connect(): bool
    {
        if ($this->connection !== null) {
            return true;
        }

        $settings = $this->settings;
        $connection = null;
        try {
            $str = "host={$settings['hostname']}"
                . " dbname={$settings['dbName']}"
                . " user={$settings['username']}"
                . " password={$settings['password']}";
            $connection = pg_connect($str);
        } catch (\Exception $e) {
            $this->addFlightRecord($e->getMessage());
            return false;
        }

        $this->connection = $connection;
        return true;
    }

    public function disconnect(): bool
    {
        if ($this->connection === null) {
            return true;
        }
        
        $result = pg_close($this->connection);
        if (!$result) {
            $this->addFlightRecord(pg_last_error($this->connection));
            return false;
        }

        $this->connection = null;
        return true;
    }

    public function getTableSchema(string $tableName): ?DbTableSchema
    {
        list($schemaName, $shortTableName) = $this->splitTableName($tableName);
        if (array_key_exists($schemaName, $this->schemas)
            && array_key_exists($shortTableName, $this->schemas[$schemaName])
        ) {
            return $this->schemas[$schemaName][$shortTableName];
        }
        
        $fields = $this->select("
            SELECT *
            FROM information_schema.columns
            WHERE table_schema='{$schemaName}' AND table_name='{$shortTableName}'
        ", DbConnection::SELECT_TYPE_ASSOC, false);

        if (empty($fields)) {
            return null;
        }

        $fieldDefinitions = [];
        foreach ($fields as $field) {
            $fieldName = $field['column_name'];
            $definition = ['name' => $fieldName];

            //TODO конвертить какие-то еще типы? Как минимум всякие smallint с инициализацией size
            $type = $field['data_type'];
            if (preg_match('/^character/', $type)) {
                $type = DbTableField::TYPE_STRING;
            } elseif (preg_match('/^timestamp/', $type)) {
                //TODO use with timezone
                $type = DbTableField::TYPE_TIMESTAMP;
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
                    $definition['type'] = DbTableField::TYPE_SERIAL;
                } else {
                    if (preg_match('/\'(.+?)\'::character/', $default, $matches)) {
                        $default = $matches[1];
                    }

                    if ($type == DbTableField::TYPE_BOOLEAN) {
                        $default = (strtolower($default) === 'false') ? false : true;
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
			WHERE (tc.constraint_type='FOREIGN KEY' OR tc.constraint_type='PRIMARY KEY' OR tc.constraint_type='UNIQUE')
				AND tc.table_schema='$schemaName'
				AND tc.table_name='$shortTableName';
		", DbConnection::SELECT_TYPE_ASSOC, false);
        $fks = [];
        foreach ($constraintKeys as $key) {
            $fieldName = $key['column_name'];
            switch ($key['constraint_type']) {
                case 'PRIMARY KEY':
                    $config['fields'][$fieldName]['pk'] = true;
                    break;
                case 'FOREIGN KEY':
                    $fks[$key['constraint_name']][] = [
                        'field' => $fieldName,
                        'relTable' => $key['table_schema'] . '.' . $key['table_name'],
                        'relField' => $key['rel_column_name'],
                    ];
                    break;
                case 'UNIQUE':
                    $config['fields'][$fieldName]['attributes'][] = DbTableField::ATTRIBUTE_UNIQUE;
                    break;
            }
        }
        foreach ($fks as $fkName => $fk) {
            if (count($fk) == 1) {
                $data = $fk[0];
                $config['fields'][$data['field']]['fk'] = [
                    'table' => $data['relTable'],
                    'field' => $data['relField'],
                    'name' => $fkName,
                ];
            } else {
                $fields = [];
                $relFields = [];
                $relTable = $fk[0]['relTable'];
                foreach ($fk as $data) {
                    if (!in_array($data['field'], $fields)) {
                        $fields[] = $data['field'];
                    }
                    if (!in_array($data['relField'], $relFields)) {
                        $relFields[] = $data['relField'];
                    }
                }
                $config['fk'][] = [
                    'fields' => $fields,
                    'relatedTable' => $relTable,
                    'relatedFields' => $relFields,
                    'name' => $fkName
                ];
            }
        }

        $schema = DbTableSchema::createByConfig($config);
        $schema->setDb($this);

        $this->schemas[$schemaName][$shortTableName] = $schema;
        return $schema;
    }

    public function getContrForeignKeysInfo(string $tableName, ?array $fields = null): array
    {
        list($schemaName, $shortTableName) = $this->splitTableName($tableName);
        $query = "
            SELECT
                ccu.column_name as rel_field_name,
                kcu.table_schema || '.' || kcu.table_name as table_name,
                kcu.column_name as field_name,
                tc.constraint_name as fk_name
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
        ";
        if ($fields) {
            foreach ($fields as &$field) {
                $field = $this->convertValueForQuery($field);
            }
            unset($field);
            $fields = implode(',', $fields);
            $query .= " AND ccu.column_name in ($fields)";
        }

        $constraints = $this->select($query, DbConnection::SELECT_TYPE_ASSOC, false);

        $fks = [];
        foreach ($constraints as $constraint) {
            $fks[$constraint['fk_name']][] = [
                'field' => $constraint['field_name'],
                'table' => $constraint['table_name'],
                'relTable' => $tableName,
                'relField' => $constraint['rel_field_name'],
            ];
        }

        $result = [];
        foreach ($fks as $fkName => $fk) {
            $fields = [];
            $relFields = [];
            $table = $fk[0]['table'];
            $relTable = $fk[0]['relTable'];
            foreach ($fk as $data) {
                if (!in_array($data['field'], $fields)) {
                    $fields[] = $data['field'];
                }
                if (!in_array($data['relField'], $relFields)) {
                    $relFields[] = $data['relField'];
                }
            }
            $result[] = [
                'name' => $fkName,
                'table' => $table,
                'fields' => $fields,
                'relatedTable' => $relTable,
                'relatedFields' => $relFields,
            ];
        }
        
        return $result;
    }

    public function getTableName(string $name): string
    {
        return $name;
    }

    public function tableExists(string $name): bool
    {
        list($schemaName, $shortTableName) = $this->splitTableName($name);
        $res = $this->select("
            SELECT * FROM pg_tables where schemaname='{$schemaName}' AND tablename='{$shortTableName}'
        ", DbConnection::SELECT_TYPE_NUM, false);
        return !empty($res);
    }

    public function renameTable(string $oldName, string $newName): bool
    {
        return $this->query("ALTER TABLE $oldName RENAME TO $newName;");
    }

    /**
     * @return mixed
     */
    public function query(string $query)
    {
        if (preg_match('/^\s*(SELECT)/i', $query)) {
            return $this->select($query);
        }

        if (preg_match('/^\s*INSERT/i', $query)) {
            return $this->insert($query);
        }

        $res = pg_query($this->connection, $query);
        if ($res === false) {
            $this->addFlightRecord(pg_last_error($this->connection));
            return false;
        }
        
        pg_free_result($res);
        return true;
    }

    public function massUpdate(string $tableName, array $rows): bool
    {
        /*
        -- This query will work:
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

        $sampleRow = $rows[0] ?? null;
        if (!$sampleRow) {
            $this->addFlightRecord('Nothing to update');
            return false;
        }

        $schema = $this->getTableSchema($tableName);
        $pks = [];
        foreach ($sampleRow as $key => $value) {
            if (!$schema->hasField($key)) {
                $this->addFlightRecord("The table does not have column $key");
                return false;
            }

            $field = $schema->getField($key);
            if ($field->isPk()) {
                $pks[] = $key;
            }
        }
        if (empty($pks)) {
            $this->addFlightRecord('There are no any primary keys for rows matching');
            return false;
        }

        $set = [];
        $values = [];
        foreach ($schema->getFields() as $fieldName => $field) {
            $vals = [];
            foreach ($rows as $row) {
                if ($row !== $sampleRow) {
                    $diff = array_diff(array_keys($sampleRow), array_keys($row));
                    if (!empty($diff)) {
                        $this->addFlightRecord('All rows have to use the same structure');
                        return false;
                    }
                }

                $val = $this->convertValueForQuery($row[$fieldName] ?? null);
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
        
        $query = "UPDATE {$tableName} SET $set FROM (SELECT $values) as t WHERE ";
        $pkConditions = [];
        foreach ($pks as $pk) {
            $pkConditions[] = "{$tableName}.{$pk} = t.{$pk}";
        }
        $query .= implode(' AND ', $pkConditions);
        
        $res = pg_query($this->connection, $query);
        if ($res === false) {
            $this->addFlightRecord(pg_last_error($this->connection));
            return false;
        }

        pg_free_result($res);
        return true;
    }

    public function getCreateTableQuery(DbTableSchema $schema): string
    {
        $name = $schema->getName();
        $query = '';
        if (preg_match('/\./', $name)) {
            $arr = explode('.', $name);
            $query = "CREATE SCHEMA IF NOT EXISTS {$arr[0]};";
        }

        $query .= "CREATE TABLE IF NOT EXISTS $name (";

        $cols = [];
        $fields = $schema->getFields();
        foreach ($fields as $field) {
            $cols[] = $this->fieldToString($field);
        }
        $cols = implode(', ', $cols);

        $query .= "$cols);";

        $pkNames = $schema->getPKNames();
        if (!empty($pkNames)) {
            $pKeyName = str_replace('.', '_', $name) . '_pkey';
            $pks = implode(', ', $pkNames);
            $query .= "ALTER TABLE $name ADD CONSTRAINT $pKeyName PRIMARY KEY ($pks);";
        }

        $fks = $schema->getForeignKeysInfo();
        foreach ($fks as $fk) {
            $fkQuery = $this->getAddForeignKeyQuery(
                $fk->getTableName(),
                $fk->getFieldNames(),
                $fk->getRelatedTableName(),
                $fk->getRelatedFieldNames(),
                $fk->getName()
            );
            $query .= $fkQuery . ';';
        }

        return $query;
    }

    /**
     * @param string|array $fields
     * @param string|array $relFields
     */
    public function getAddForeignKeyQuery(
        string $tableName,
        $fields,
        string $relTableName,
        $relFields,
        ?string $constraintName = null
    ): string
    {
        $fields = (array)$fields;
        $relFields = (array)$relFields;

        if ($constraintName === null) {
            $tableKey = str_replace('.', '_', $tableName);
            $constraintName = "fk__{$tableKey}__" . implode('__', $fields);
        }

        $fields = implode(', ', $fields);
        $relFields = implode(', ', $relFields);
        //TODO ON DELETE|UPDATE CASCADE|RESTRICT
        return "ALTER TABLE {$tableName} ADD CONSTRAINT $constraintName "
            . "FOREIGN KEY ({$fields}) REFERENCES {$relTableName}({$relFields})";
    }

    /**
     * @param string|array $fields
     */
    public function getDropForeignKeyQuery(
        string $tableName,
        $fields,
        ?string $constraintName = null
    ): string
    {
        if ($constraintName === null) {
            $fields = (array)$fields;
            $tableKey = str_replace('.', '_', $tableName);
            $constraintName = "fk__{$tableKey}__" . implode('__', $fields);
        }

        return"ALTER TABLE $tableName DROP CONSTRAINT $constraintName";
    }
    
    public function getAddColumnQuery(string $tableName, DbTableField $field): string
    {
        $definition = $this->fieldToString($field);
        //TODO if ($field->isFk())

        return "ALTER TABLE {$tableName} ADD COLUMN {$definition}";
    }

    public function getDelColumnQuery(string $tableName, string $fieldName): string
    {
        return "ALTER TABLE {$tableName} DROP COLUMN {$fieldName}";
    }

    public function getChangeColumnQuery(string $tableName, DbTableField $field): string
    {
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
            $default = $this->convertValueForQuery($default);
            $queries[] = "ALTER TABLE $tableName ALTER COLUMN $fieldName SET DEFAULT $default;";
        }
        
        $attributes = $field->getAttributes();
        $key = "unique__$tableName__$fieldName";
        if (in_array(DbTableField::ATTRIBUTE_UNIQUE, $attributes)) {
            $queries[] = "ALTER TABLE $tableName ADD CONSTRAINT $key UNIQUE ($fieldName);";
        } else {
            $queries[] = "ALTER TABLE $tableName DROP CONSTRAINT $key;";
        }
        
        //TODO if ($field->isFk())

        $query = implode('', $queries);
        return $query;
    }

    public function getRenameColumnQuery(string $tableName, string $oldFieldName, string $newFieldName): string
    {
        return "ALTER TABLE {$tableName} RENAME COLUMN {$oldFieldName} TO {$newFieldName}";
    }

    /**
     * @param mixed $value
     */
    public function convertValueForQuery($value): string
    {
        if (is_string($value)) return "'$value'";
        if (is_bool($value)) return $value ? 'TRUE' : 'FALSE';
        if (is_null($value)) return 'NULL';
        return (string)$value;
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


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * PRIVATE
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	private function select(string $query, int $selectType = DbConnection::SELECT_TYPE_ASSOC, bool $useParser = true)
    {
        if ($useParser) {
            try {
                $queryObject = new DbSelectQuery($this, $query);
            } catch (\Exception $exception) {
                $this->addFlightRecord($exception->getMessage());
                return false;
            }
        }

		$res = pg_query($this->connection, $query);
		if ($res === false) {
			$this->addFlightRecord(pg_last_error($this->connection));
			return false;//TODO null
		}

		switch ($selectType) {
            case DbConnection::SELECT_TYPE_BOTH:
                $resultType = PGSQL_BOTH;
                break;
            case DbConnection::SELECT_TYPE_NUM:
                $resultType = PGSQL_NUM;
                break;
            case DbConnection::SELECT_TYPE_ASSOC:
            default:
                $resultType = PGSQL_ASSOC;
        }

		$arr = [];
		while ($row = pg_fetch_array($res, null, $resultType)) {
		    if ($useParser) {
                foreach ($row as $name => &$value) {
                    $field = $queryObject->getField($name);
                    if ($field) {
                        switch ($field->getType()) {
                            case DbTableField::TYPE_SERIAL:
                            case DbTableField::TYPE_INTEGER:
                                $value = (int)$value;
                                break;
                            case DbTableField::TYPE_BOOLEAN:
                                if ($value == 'f') $value = false;
                                elseif ($value == 't') $value = true;
                                break;
                        }
                    }
                }
                unset($value);
            }
		    $arr[] = $row;
		}
		pg_free_result($res);

		return $arr;
	}

	private function insert(string $query)
    {
        preg_match('/^insert\s+into\s+(.+?)\s/i', $query, $match);
        $tableName = $match[1];
        $schema = $this->getTableSchema($tableName);

        $withRetirning = false;
        $pkNames = $schema->getPKNames();
        foreach ($pkNames as $pkName) {
            $pk = $schema->getField($pkName);
            if ($pk->getType() == DbTableField::TYPE_SERIAL) {
                $query .= " RETURNING $pkName";
                $withRetirning = true;
                break;
            }
        }

        $res = pg_query($this->connection, $query);
        if ($res === false) {
            $this->addFlightRecord(pg_last_error($this->connection));
            return false;
        }
        if (!$withRetirning) {
            return true;
        }

        $arr = [];
        while ($row = pg_fetch_array($res)) {
            $arr[] = $row[0];
        }

        pg_free_result($res);
        if (count($arr) == 1) {
            return $arr[0];
        }

        sort($arr);
        return $arr;
	}
	
    /**
     * @return array [schemaName: string, shortTableName: string]
     */	
	private function splitTableName(string $tableName): array
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
    
    private function fieldToString(DbTableField $field): string
    {
        $result = $field->getName() . ' ' . $field->getTypeDefinition();

        $attributes = $field->getAttributes();
        if (!empty($attributes)) {
            $result .= ' ' . implode(' ' , $attributes);
        }

        if (!$field->isNullable()) {
            $result .= ' not null';
        }

        $default = $field->getDefault();
        if ($default !== null) {
            $default = $this->convertValueForQuery($default);
            $result .= " default {$default}";
        }

        return $result;
    }
}
