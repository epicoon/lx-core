<?php

namespace lx;

class DbTable
{
    /** @var DbConnectionInterface */
    private $db;
    /** @var string */
    private $name;

    public function __construct(DbConnectionInterface $db, string $name)
    {
        $this->db = $db;
        $this->name = $name;
    }

    public function getDb(): DbConnectionInterface
    {
        return $this->db;
    }

    public function getName(): string
    {
        return $this->name;
    }
    
    public function getSchema(): DbTableSchema
    {
        return $this->db->getTableSchema($this->name);
    }

    /**
     * @deprecated
     */
    public function pkName()
    {
        return 'id';
    }

    public function select($fields = '*', $condition = null)
    {
        if (is_array($fields)) {
            $fields = implode(',', $fields);
        }
        $query = "SELECT $fields FROM {$this->name}";
        $condition = $this->parseCondition($condition);
        if (!$this->validateCondition($condition)) {
            return [];
        }
        $condition = $this->renderCondition($condition);

        if ($condition) $query .= $condition;

        $result = $this->db->query($query);
        return $result;
    }

    public function selectColumn($columnName, $condition = null)
    {
        $data = $this->select($columnName, $condition);
        $result = [];
        foreach ($data as $row) {
            $result[] = $row[$columnName];
        }

        return $result;
    }

    public function insert($fields, $values = null)
    {
        if ($values === null) {
            $values = array_values($fields);
            $fields = array_keys($fields);
        }

        $query = 'INSERT INTO ' . $this->name . ' (' . implode(', ', $fields) . ')' . ' VALUES ';
        $valstr = [];
        if (!is_array($values[0])) $values = [$values];
        foreach ($values as $valueSet) {
            $valueSet = (array)$valueSet;
            foreach ($valueSet as &$value) {
                $value = $this->db->convertValueForQuery($value);
            }
            unset($value);
            $valstr[] = '(' . implode(', ', $valueSet) . ')';
        }
        $query .= implode(', ', $valstr);
        return $this->db->query($query);
    }

    /**
     * @param array|string|int|null $condition
     */
    public function update(array $sets, $condition = null): bool
    {
        $temp = [];
        foreach ($sets as $field => $value) {
            $temp[] = $field . ' = ' . $this->db->convertValueForQuery($value);
        }
        $temp = implode(', ', $temp);

        $condition = $this->parseCondition($condition);
        if (!$this->validateCondition($condition)) {
            return false;
        }
        $condition = $this->renderCondition($condition);

        $query = "UPDATE {$this->name} SET $temp";
        if ($condition !== null) {
            $query .= $condition;
        }

        return $this->db->query($query);
    }

    /**
     * @param array|string|int|null $condition
     */
    public function delete($condition = null): bool
    {
        $condition = $this->parseCondition($condition);
        if (!$this->validateCondition($condition)) {
            return false;
        }
        $condition = $this->renderCondition($condition);

        $query = 'DELETE FROM ' . $this->name;
        if ($condition !== null) {
            $query .= $condition;
        }
        return $this->db->query($query);
    }

    public function massUpdate(array $rows): bool
    {
        return $this->db->massUpdate($this->getName(), $rows);
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

    private function renderCondition(DataObject $data): ?string
    {
        if ($data->isEmpty()) {
            return null;
        }

        $schema = $this->getSchema();
        $whereText = '';
        foreach ($data->where as $key => $value) {
            if (is_string($key)) {
                if (is_array($value)) {
                    foreach ($value as &$val) {
                        if (!is_string($val) && $schema->getField($key)->getType() == DbTableField::TYPE_STRING) {
                            $val = (string)$val;
                        }
                        $val = $this->db->convertValueForQuery($val);
                    }
                    unset($val);
                    $part = $key . ' IN (' . implode(', ', $value) . ')';
                } else {
                    if (!is_string($value) && $schema->getField($key)->getType() == DbTableField::TYPE_STRING) {
                        $value = (string)$value;
                    }
                    $part = $key . '=' . $this->db->convertValueForQuery($value);
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
}
