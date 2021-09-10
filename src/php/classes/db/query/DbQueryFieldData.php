<?php

namespace lx;

class DbQueryFieldData
{
    private DbQueryTableData $table;
    private string $name;
    private ?string $aliase;

    public function __construct(DbQueryTableData $table, string $name, ?string $aliase = null)
    {
        $this->table = $table;
        $this->name = $name;
        $this->aliase = $aliase;
    }
    
    public function getTable(): DbQueryTableData
    {
        return $this->table;
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
    
    public function getType(): string
    {
        $schema = $this->table->getSchema();
        $field = $schema->getField($this->getRealName());
        return $field->getType();
    }
}
