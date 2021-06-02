<?php

namespace lx;

class DbTableField
{
    const TYPE_SERIAL = 'serial';
    const TYPE_INTEGER = 'integer';
    const TYPE_STRING = 'varchar';
    const TYPE_FLOAT = 'float';
    const TYPE_DECIMAL = 'decimal';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_TIMESTAMP = 'timestamp';
    
    const ATTRIBUTE_UNIQUE = 'uniqie';

    private string $name;
    private string $type;
    private array $attributes;
    private ?int $size;
    private bool $isNullable;
    /** @var mixed */
    private $defailt;
    private bool $pk;
    private ?array $fk;

    public function __construct(array $definition)
    {
        $this->name = $definition['name'];
        $this->type = $definition['type'] ?? self::TYPE_STRING;
        $this->attributes = $definition['attributes'] ?? [];
        $this->size = $definition['size'] ?? null;
        if ($this->size !== null) {
            $this->size = (int)$this->size;
        }
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

        $size = $this->getSize();
        if ($size !== null) {
            $result['size'] = $size;
        }

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
        if ($this->size === null) {
            return $this->type;
        }

        return $this->type . '(' . $this->size . ')';
    }
    
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    /**
     * @return mixed|null
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

    public function getFkConfig(): ?array
    {
        return $this->fk;
    }
}
