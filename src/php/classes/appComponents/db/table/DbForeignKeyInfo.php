<?php

namespace lx;

class DbForeignKeyInfo
{
    private DbTableSchema $schema;
    private ?string $name;
    private string $table;
    private array $fields;
    private string $relatedTable;
    private array $relatedFields;

    public function __construct(DbTableSchema $schema, array $config)
    {
        $this->schema = $schema;
        $this->name = $config['name'] ?? null;
        $this->table = $config['table'];
        $this->fields = $config['fields'];
        $this->relatedTable = $config['relatedTable'];
        $this->relatedFields = $config['relatedFields'];
    }
    
    public function getName(): ?string
    {
        return $this->name;
    }
    
    public function getTableName(): string
    {
        return $this->table;
    }

    public function getRelatedTableName(): string
    {
        return $this->relatedTable;
    }

    public function getFieldNames(): array
    {
        return $this->fields;
    }

    public function getRelatedFieldNames(): array
    {
        return $this->relatedFields;
    }
}
