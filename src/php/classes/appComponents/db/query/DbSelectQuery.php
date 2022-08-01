<?php

namespace lx;

class DbSelectQuery
{
    private DbConnectionInterface $db;
    /** @var array<DbQueryTableData> */
    private array $tables = [];
    /** @var array<DbQueryFieldData> */
    private array $fields = [];

    public function __construct(DbConnectionInterface $db, string $query)
    {
        $this->db = $db;
        
        $parser = new DbSelectParser();
        $queryData = $parser->parse($query);

        foreach ($queryData['from'] as $name) {
            $data = new DbQueryTableData($this->db, $name[0], $name[1]);
            $this->tables[$data->getName()] = $data;
        }
        if (array_key_exists('join', $queryData)) {
            foreach ($queryData['join'] as $datum) {
                $name = $datum[0];
                $data = new DbQueryTableData($this->db, $name[0], $name[1]);
                $this->tables[$data->getName()] = $data;
            }
        }
        
        foreach ($queryData['select'] as $datum) {
            $table = $datum[0];
            $name = $datum[1];
            if ($name == '*') {
                if ($table !== null) {
                    $tableData = $this->tables[$table];
                    $schema = $this->getTableSchema($tableData->getRealName());
                    foreach ($schema->getFields() as $fieldName => $field) {
                        $this->fields[$tableData->getName() . '.' . $fieldName]
                            = new DbQueryFieldData($tableData, $fieldName);
                    }
                } else {
                    foreach ($this->tables as $tableData) {
                        $schema = $this->getTableSchema($tableData->getRealName());
                        foreach ($schema->getFields() as $fieldName => $field) {
                            $this->fields[$tableData->getName() . '.' . $fieldName]
                                = new DbQueryFieldData($tableData, $fieldName);
                        }
                    }
                }
                continue;
            }

            $aliase = $datum[2];
            if ($table === null) {
                foreach ($this->tables as $tableData) {
                    $schema = $this->getTableSchema($tableData->getRealName());
                    if ($schema->hasField($name)) {
                        if ($table === null) {
                            $table = $tableData;
                        } else {
                            throw new \Exception("The field '$name' is assotiated with several tables");
                        }
                    }
                }
            } else {
                if (array_key_exists($table, $this->tables)) {
                    $table = $this->tables[$table];
                } else {
                    throw new \Exception("The field '$name' is assotiated with unknown table '$table'");
                }
            }

            if ($table) {
                $this->fields[$aliase ?? $table->getName() . '.' . $name] = new DbQueryFieldData($table, $name, $aliase);
            }
        }
    }

    public function getField($name): ?DbQueryFieldData
    {
        if (array_key_exists($name, $this->fields)) {
            return $this->fields[$name];
        }
        
        foreach ($this->fields as $field) {
            if ($field->getRealName() == $name || $field->getName() == $name) {
                return $field;
            }
        }
        
        return null;
    }

    private function getTableSchema(string $tableName): DbTableSchema
    {
        $schema = $this->db->getTableSchema($tableName);
        if ($schema === null) {
            throw new \Exception("Table $tableName does not exist");
        }
        return $schema;
    }
}
