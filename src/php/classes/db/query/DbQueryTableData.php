<?php

namespace lx;

class DbQueryTableData
{
    private DbConnectionInterface $db;
    private string $name;
    private ?string $aliase;
    
    public function __construct(DbConnectionInterface $db, string $name, ?string $aliase)
    {
        $this->db = $db;
        $this->name = $name;
        $this->aliase = $aliase;
    }
    
    public function getName(): string
    {
        return $this->aliase ?? $this->name;
    }

    public function getRealName(): string
    {
        return $this->name;
    }

    public function getAliase(): string
    {
        return $this->aliase;
    }
    
    public function getSchema(): DbTableSchema
    {
        return $this->db->getTableSchema($this->name);
    }
}
