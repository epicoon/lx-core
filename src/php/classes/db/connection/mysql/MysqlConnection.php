<?php

namespace lx;

use Exception;

class MysqlConnection extends DbConnection
{
    public function connect(): bool
    {
        if ($this->connection !== null) {
            return true;
        }

        $settings = $this->settings;
        $connection = null;
        try {
            $connection = mysqli_connect($settings['hostname'], $settings['username'], $settings['password']);
            mysqli_select_db($connection, $settings['dbName']);
            mysqli_set_charset($connection, 'utf8');
        } catch (Exception $e) {
            if ($connection) {
                mysqli_close($connection);
            }

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

        $result = mysqli_close($this->connection);
        if (!$result) {
            $this->addFlightRecord(mysqli_error($this->connection));
            return false;
        }

        $this->connection = null;
        return true;
    }

    /**
     * @return mixed
     */
    public function query($query)
    {
        if (preg_match('/^\s*SELECT/', $query)) {
            return $this->select($query);
        }

        $res = mysqli_query($this->connection, $query);
        if ($res === false) {
            $this->addFlightRecord(mysqli_error($this->connection));
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
     * Проверяет существование таблицы
     * */
    public function tableExists(string $name): bool
    {
        $name = $this->getTableName($name);
        $res = $this->select("SHOW TABLES FROM {$this->settings['dbName']} LIKE '$name'");
        return !empty($res);
    }

    public function renameTable($oldName, $newName): bool
    {
        return $this->query("RENAME TABLE $oldName TO $newName;");
    }

    public function getTableSchema(string $tableName): ?DbTableSchema
    {
        //todo доделать метод
        return null;
    }

    public function getContrForeignKeysInfo(string $tableName, ?array $fields = null): array
    {
        $realTableName = $this->getTableName($tableName);
        $schemaName = $this->settings['dbName'];

        $query = "
            SELECT 
                REFERENCED_COLUMN_NAME as rel_field_name,
                REPLACE(TABLE_NAME, '__', '.') as table_name,
                COLUMN_NAME as field_name,
                CONSTRAINT_NAME as fk_name,
            FROM 
              KEY_COLUMN_USAGE
            WHERE 
              TABLE_SCHEMA = '$schemaName' 
            AND TABLE_NAME = '$realTableName' 
            AND REFERENCED_COLUMN_NAME is not NULL
        ";

        if ($fields) {
            foreach ($fields as &$field) {
                $field = $this->getQueryBuilder()->convertValueForQuery($field);
            }
            unset($field);
            $fields = implode(',', $fields);
            $query .= " AND REFERENCED_COLUMN_NAME in ($fields)";
        }

        $constraints = $this->select($query);

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
        return str_replace('.', '__', $name);
    }

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
    * PRIVATE
    * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * @param $query
     * @return array|false
     */
    private function select($query)
    {
        $res = mysqli_query($this->connection, $query);
        if ($res === false) {
            $this->addFlightRecord(mysqli_error($this->connection));
            return false;
        }

        $mode = MYSQLI_ASSOC;

        $arr = [];
        while ($row = mysqli_fetch_array($res, $mode)) {
            $arr[] = $row;
        }

        return $arr;
    }
}
