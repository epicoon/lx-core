<?php

namespace lx;

class PostgresConnection extends DbConnection
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
            } elseif (preg_match('/^time /', $type)) {
                $type = DbTableField::TYPE_TIME;
            } elseif (preg_match('/^date /', $type)) {
                $type = DbTableField::TYPE_DATE;
            } elseif (preg_match('/^double/', $type)) {
                $type = DbTableField::TYPE_FLOAT;
            }
            $definition['type'] = $type;

            $details = [];
            // VARCHAR
            $size = $field['character_maximum_length'] ?? null;
            if ($size !== null) {
                $details['size'] = (int)$size;
            }
            // DECIMAL|NUMERIC
            $precision = $field['numeric_precision'] ?? null;
            if ($precision !== null) {
                $details['precision'] = $precision;
            }
            $scale = $field['numeric_scale'] ?? null;
            if ($scale !== null) {
                $details['scale'] = $scale;
            }
            if (!empty($details)) {
                $definition['details'] = $details;
            }

            $definition['nullable'] = ($field['is_nullable'] == 'YES');

            $default = $field['column_default'];
            if ($default !== null) {
                if (preg_match('/^nextval\(.*seq/', $default)) {
                    $definition['type'] = DbTableField::TYPE_SERIAL;
                } else {
                    if (preg_match('/\'(.+?)\'::[\w ]+$/', $default, $matches)) {
                        $default = $matches[1];
                    }

                    //TODO еще типы обрабатывать? Надо парсеры под полиморфизмом
                    if ($type == DbTableField::TYPE_BOOLEAN) {
                        $default = (strtolower($default) === 'false') ? false : true;
                    } elseif ($type == DbTableField::TYPE_DECIMAL) {
                        $e = 1;
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
                $field = $this->getQueryBuilder()->convertValueForQuery($field);
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

	private function select(
        string $query,
        int $selectType = DbConnection::SELECT_TYPE_ASSOC,
        bool $useParser = true
    ): ?array
    {
        if ($useParser) {
            try {
                $queryObject = new DbSelectQuery($this, $query);
            } catch (\Exception $exception) {
                $this->addFlightRecord($exception->getMessage());
                return null;
            }
        }

		$res = pg_query($this->connection, $query);
		if ($res === false) {
			$this->addFlightRecord(pg_last_error($this->connection));
			return null;
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
}
