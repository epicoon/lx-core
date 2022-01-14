<?php

namespace lx;

class PostgresQueryBuilder extends DbQueryBuilder
{
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
     * @param string|array $fields
     * @param array|string|int|null $condition
     */
    public function getSelectQuery(string $tableName, $fields = '*', $condition = null): ?string
    {
        if (is_array($fields)) {
            $fields = implode(',', $fields);
        }
        $query = "SELECT $fields FROM {$tableName}";
        $condition = $this->parseCondition($condition);
        if (!$this->validateCondition($condition)) {
            return null;
        }
        $condition = $this->renderCondition($tableName, $condition);

        if ($condition) {
            $query .= $condition;
        }

        return $query;
    }

    /**
     * @param array|string|int|null $condition
     */
    public function getInsertQuery(string $tableName, array $fields, $values = null): ?string
    {
        if ($values === null) {
            $values = array_values($fields);
            $fields = array_keys($fields);
        }

        $query = 'INSERT INTO ' . $tableName . ' (' . implode(', ', $fields) . ')' . ' VALUES ';
        $valstr = [];
        if (!is_array($values[0])) $values = [$values];
        foreach ($values as $valueSet) {
            $valueSet = (array)$valueSet;
            foreach ($valueSet as &$value) {
                $value = $this->convertValueForQuery($value);
            }
            unset($value);
            $valstr[] = '(' . implode(', ', $valueSet) . ')';
        }
        $query .= implode(', ', $valstr);
        return $query;
    }

    /**
     * @param array|string|int|null $condition
     */
    public function getUpdateQuery(string $tableName, array $sets, $condition = null): ?string
    {
        $temp = [];
        foreach ($sets as $field => $value) {
            $temp[] = $field . ' = ' . $this->convertValueForQuery($value);
        }
        $temp = implode(', ', $temp);

        $condition = $this->parseCondition($condition);
        if (!$this->validateCondition($condition)) {
            return null;
        }
        $condition = $this->renderCondition($tableName, $condition);

        $query = "UPDATE {$tableName} SET $temp";
        if ($condition !== null) {
            $query .= $condition;
        }

        return $query;
    }

    public function getMassUpdateQuery(string $tableName, array $rows): ?string
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
            return null;
        }

        $schema = $this->getTableSchema($tableName);
        $pks = [];
        foreach ($sampleRow as $key => $value) {
            if (!$schema->hasField($key)) {
                $this->addFlightRecord("The table does not have column $key");
                return null;
            }

            $field = $schema->getField($key);
            if ($field->isPk()) {
                $pks[] = $key;
            }
        }
        if (empty($pks)) {
            $this->addFlightRecord('There are no any primary keys for rows matching');
            return null;
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
                        return null;
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

        return $query;
    }

    /**
     * @param array|string|int|null $condition
     */
    public function getDeleteQuery(string $tableName, $condition = null): ?string
    {
        $condition = $this->parseCondition($condition);
        if (!$this->validateCondition($condition)) {
            return null;
        }
        $condition = $this->renderCondition($tableName, $condition);

        $query = 'DELETE FROM ' . $tableName;
        if ($condition !== null) {
            $query .= $condition;
        }

        return $query;
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


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * PRIVATE
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * TODO жесткая привязка имени первичного ключа
     * @deprecated
     */
    private function pkName()
    {
        return 'id';
    }

    /**
     * @param array|string|int|null $condition
     */
    private function parseCondition($condition): DataObject
    {
        $data = new DataObject();
        $data->where = [];

        if ($condition === null) {
            return $data;
        }

        // Проверка что это число
        if (filter_var($condition, FILTER_VALIDATE_INT) !== false) {
            $data->where += [$this->pkName() => $condition];
        } elseif (is_string($condition)) {
            $data->where[] = $condition;
        } elseif (is_array($condition)) {
            $isMap = false;
            if (array_key_exists('WHERE', $condition)) {
                $isMap = true;
                $where = $condition['WHERE'];
            }

            if (array_key_exists('ORDER BY', $condition)) {
                $isMap = true;
                $data->order = $condition['ORDER BY'];
            }

            if (array_key_exists('OFFSET', $condition) && $condition['OFFSET'] != 0) {
                $isMap = true;
                $data->offset = $condition['OFFSET'];
            }

            if (array_key_exists('LIMIT', $condition) && $condition['LIMIT'] !== null) {
                $isMap = true;
                $data->limit = $condition['LIMIT'];
            }

            if (!$isMap) {
                $where = $condition;
            }

            if (isset($where)) {
                if (is_array($where)) {
                    $isIds = true;
                    foreach ($where as $key => $item) {
                        if (is_string($key) || filter_var($item, FILTER_VALIDATE_INT) === false) {
                            $isIds = false;
                            break;
                        }
                    }
                } else {
                    $isIds = filter_var($where, FILTER_VALIDATE_INT);
                }

                if (is_string($where)) {
                    $data->where[] = $where;
                } else {
                    $data->where += $isIds ? [$this->pkName() => $where] : $where;
                }
            }
        }

        return $data;
    }

    private function validateCondition(DataObject $data): bool
    {
        foreach ($data->where as $key => $value) {
            if (is_array($value) && empty($value)) {
                return false;
            }
        }

        return true;
    }

    private function renderCondition(string $tableName, DataObject $data): ?string
    {
        if ($data->isEmpty()) {
            return null;
        }

        $schema = $this->getConnection()->getTableSchema($tableName);
        $whereText = '';
        foreach ($data->where as $key => $value) {
            if (is_string($key)) {
                if (is_array($value)) {
                    foreach ($value as &$val) {
                        if (!is_string($val) && $schema->getField($key)->getType() == DbTableField::TYPE_STRING) {
                            $val = (string)$val;
                        }
                        $val = $this->convertValueForQuery($val);
                    }
                    unset($val);
                    $part = $key . ' IN (' . implode(', ', $value) . ')';
                } else {
                    if (!is_string($value) && $schema->getField($key)->getType() == DbTableField::TYPE_STRING) {
                        $value = (string)$value;
                    }
                    $part = $key . '=' . $this->convertValueForQuery($value);
                }
            } else {
                $part = $value;
            }

            if (!preg_match('/^AND/', $part) && !preg_match('/^OR/', $part)) {
                $part = ' AND ' . $part;
            }

            $whereText .= $part;
        }

        $result = '';

        if ($whereText != '') {
            $whereText = preg_replace('/^ AND /', '', $whereText);
            $result .= ' WHERE (' . $whereText . ')';
        }

        if ($data->order) {
            $result .= ' ORDER BY ' . $data->order;
        }

        if ($data->limit) {
            $result .= ' LIMIT ' . $data->limit;
        }

        if ($data->offset) {
            $result .= ' OFFSET ' . $data->offset;
        }

        return $result;
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
