<?php

namespace lx;

class DbTable
{
    private $db;
    private $name;
    private $_pkName = null;

    public function __construct($name, $db)
    {
        $this->name = $name;
        $this->db = $db;
    }

    public function getDb()
    {
        return $this->db;
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * @deprecated
     */
    public function schema($columns = DB::SHORT_SCHEMA)
    {
        if ($columns == DB::SHORT_SCHEMA) {
            return DbTableSchemaProvider::get($this);
        }

        return $this->db->tableSchema($this->name, $columns);
    }

    /**
     * @deprecated
     */
    public function pkName()
    {
        if ($this->_pkName === null) {
            //TODO
            $this->_pkName = 'id';
        }

        return $this->_pkName;
    }

    /**
     * @deprecated
     */
    public function setPkName($name)
    {
        $this->_pkName = $name;
    }

    public function select($fields = '*', $condition = null)
    {
        if (is_array($fields)) {
            $fields = implode(',', $fields);
        }
        $query = "SELECT $fields FROM {$this->name}";
        $condition = $this->parseCondition($condition);
        if ($condition) $query .= $condition;

        $result = $this->db->select($query);
        $result = $this->db->normalizeTypes($this, $result);
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

    public function insert($fields, $values=null, $returnId=true)
    {
        if ($values === null || is_bool($values)) {
            $returnId = $values === null ? true : $values;
            $values = array_values($fields);
            $fields = array_keys($fields);
        }

        $query = 'INSERT INTO ' . $this->name . ' (' . implode(', ', $fields) . ')' . ' VALUES ';
        $valstr = [];
        if (!is_array($values[0])) $values = [$values];
        foreach ($values as $valueSet) {
            foreach ($valueSet as &$value) {
                $value = DB::valueForQuery($value);
            }
            unset($value);
            $valstr[] = '(' . implode(', ', (array)$valueSet) . ')';
        }
        $query .= implode(', ', $valstr);
        return $this->db->insert($query, $returnId);
    }

    public function update($sets, $condition=null)
    {
        $temp = [];
        foreach ($sets as $field => $value) {
            $temp[] = $field . ' = ' . DB::valueForQuery($value);
        }
        $temp = implode(', ', $temp);

        $condition = $this->parseCondition($condition);
        $query = "UPDATE {$this->name} SET $temp";
        if ($condition !== null) $query .= $condition;

        $res = $this->db->query($query);
        return $res;
    }

    public function delete($condition=null)
    {
        $condition = $this->parseCondition($condition);
        $query = 'DELETE FROM ' . $this->name;
        if ($condition !== null) $query .= $condition;
        $res = $this->db->query($query);
        return $res;
    }

    /**
     * @param array $rows
     */
    public function massUpdate($rows)
    {
        return $this->db->massUpdate($this->getName(), $rows);
    }

    protected function parseCondition($condition)
    {
        if ($condition === null) return '';

        $data = new DataObject();
        $data->where = [];

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

        $schema = $this->schema();
        $whereText = '';
        foreach ($data->where as $key => $value) {
            if (is_string($key)) {
                if (is_array($value)) {
                    foreach ($value as &$val) {
                        if (!is_string($val) && $schema->getType($key) == 'string') $val = (string)$val;
                        $val = DB::valueForQuery($val);
                    }
                    unset($val);
                    $part = $key . ' IN (' . implode(', ', $value) . ')';
                } else {
                    if (!is_string($value) && $schema->getType($key) == 'string') $value = (string)$value;
                    $part = $key . '=' . DB::valueForQuery($value);
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
