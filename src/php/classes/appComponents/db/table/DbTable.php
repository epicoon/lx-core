<?php

namespace lx;

class DbTable
{
    private DbConnectionInterface $db;
    private string $name;

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
    
    public function getSchema(): ?DbTableSchema
    {
        return $this->db->getTableSchema($this->name);
    }

    /**
     * @return array|null
     */
    public function select($fields = '*', $condition = null)
    {
        return $this->runQuery(
            $this->db->getQueryBuilder()->getSelectQuery($this->getName(), $fields, $condition)
        );
    }

    public function selectColumn($columnName, $condition = null): array
    {
        $data = $this->select($columnName, $condition);
        $result = [];
        if (!$data) {
            return $result;
        }

        foreach ($data as $row) {
            $result[] = $row[$columnName];
        }

        return $result;
    }

    /**
     * @return array|int|null
     */
    public function insert($fields, $values = null)
    {
        return $this->runQuery(
            $this->db->getQueryBuilder()->getInsertQuery($this->getName(), $fields, $values)
        );
    }

    /**
     * @param array|string|int|null $condition
     */
    public function update(array $sets, $condition = null): bool
    {
        return (bool)$this->runQuery(
            $this->db->getQueryBuilder()->getUpdateQuery($this->getName(), $sets, $condition)
        );
    }

    /**
     * @param array|string|int|null $condition
     */
    public function delete($condition = null): bool
    {
        return (bool)$this->runQuery(
            $this->db->getQueryBuilder()->getDeleteQuery($this->getName(), $condition)
        );
    }

    public function massUpdate(array $rows): bool
    {
        return (bool)$this->runQuery(
            $this->db->getQueryBuilder()->getMassUpdateQuery($this->getName(), $rows)
        );
    }

    /**
     * @return mixed|null
     */
    private function runQuery(?string $query)
    {
        if ($query === null) {
            //TODO remember error
            return null;
        }

        $res = $this->db->query($query);
        if ($this->db->hasFlightRecords()) {
            //TODO remember error
            return null;
        }

        return $res;
    }
}
