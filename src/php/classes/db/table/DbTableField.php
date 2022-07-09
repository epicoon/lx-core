<?php

namespace lx;

class DbTableField
{
    const TYPE_SERIAL = 'serial';
    const TYPE_INTEGER = 'integer';
    const TYPE_STRING = 'varchar';
    const TYPE_FLOAT = 'float';
    const TYPE_DECIMAL = 'decimal';
    const TYPE_NUMERIC = 'numeric';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_TIMESTAMP = 'timestamp';
    const TYPE_TIME_INTERVAL = 'interval';
    const TYPE_DATE = 'date';
    const TYPE_TIME = 'time';

    const ATTRIBUTE_UNIQUE = 'unique';

    private DbTableSchema $schema;
    private string $name;
    private string $type;
    private array $attributes;
    private array $details;
    private bool $isNullable;
    /** @var mixed */
    private $defailt;
    private bool $pk;
    private ?array $fk;

    public function __construct(DbTableSchema $schema, array $definition)
    {
        $this->schema = $schema;
        $this->name = $definition['name'];
        $this->type = $definition['type'] ?? self::TYPE_STRING;
        $this->attributes = $definition['attributes'] ?? [];
        $this->details = $definition['details'] ?? [];
        $this->isNullable = $definition['nullable'] ?? true;
        $this->defailt = $definition['default'] ?? null;

        $this->pk = $definition['pk'] ?? false;
        $this->fk = $definition['fk'] ?? null;
    }

    public function getDefinition(): array
    {
        $result = [
            'name' => $this->getName(),
            'type' => $this->getType(),
        ];

        $default = $this->getDefault();
        if ($default !== null) {
            $result['default'] = $default;
        }

        $isNullable = $this->isNullable();
        if (!$isNullable) {
            $result['nullable'] = $isNullable;
        }
        
        if ($this->pk) {
            $result['pk'] = true;
        }
        
        if ($this->fk) {
            $result['fk'] = $this->fk;
        }
        
        if (!empty($this->details)) {
            $result['details'] = $this->details;
        }

        return $result;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTypeDefinition(): string
    {
        if (empty($this->details)) {
            return $this->type;
        }
        
        if (array_key_exists('size', $this->details)) {
            return $this->type . '(' . $this->details['size'] . ')';
        }
        
        if (array_key_exists('precision', $this->details) && array_key_exists('scale', $this->details)) {
            return $this->type . '(' . $this->details['precision'] . ',' . $this->details['scale'] . ')';
        }
        
        return $this->type;
    }
    
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getDetails(): array
    {
        return $this->details;
    }

    /**
     * @return mixed
     */
    public function getDefault()
    {
        return $this->defailt;
    }

    public function isNullable(): bool
    {
        if ($this->isPk()) {
            return false;
        }

        return $this->isNullable;
    }

    public function isPk(): bool
    {
        return $this->pk;
    }
    
    public function isFk(): bool
    {
        return $this->fk !== null;
    }
    
    public function getForeignKeyInfo(): ?DbForeignKeyInfo
    {
        if (!$this->isFk()) {
            return null;
        }
        
        return new DbForeignKeyInfo($this->schema, [
            'name' => $this->fk['name'] ?? null,
            'table' => $this->schema->getName(),
            'fields' => [$this->getName()],
            'relatedTable' => $this->fk['table'],
            'relatedFields' => [$this->fk['field']],
        ]);
    }
}
