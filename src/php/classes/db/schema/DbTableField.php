<?php

namespace lx;

/**
 * Class DbTableField
 * @package lx
 */
class DbTableField
{
    const TYPE_INTEGER = 'integer';
    const TYPE_STRING = 'varchar';
    const TYPE_FLOAT = 'float';
    const TYPE_DECIMAL = 'decimal';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_TIMESTAMP = 'timestamp';

    /** @var DbTableSchema */
    private $schema;

    /** @var string */
    private $name;

    /** @var string */
    private $type;

    /** @var int */
    private $size;

    /** @var bool */
    private $isNullable;

    /** @var mixed */
    private $defailt;

    /** @var bool */
    private $pk;

    /** @var array|null */
    private $fk;

    /**
     * DbTableField constructor.
     * @param DbTableSchema $schema
     * @param array $definition
     */
    public function __construct($schema, $definition)
    {
        $this->schema = $schema;

        $this->name = $definition['name'];
        $this->type = $definition['type'] ?? self::TYPE_STRING;
        $this->size = $definition['size'] ?? null;
        if ($this->size !== null) {
            $this->size = (int)$this->size;
        }
        $this->isNullable = $definition['nullable'] ?? true;
        $this->defailt = $definition['default'] ?? null;

        $this->pk = $definition['pk'] ?? false;
        $this->fk = $definition['fk'] ?? null;
    }

    /**
     * @return array
     */
    public function getDefinition()
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

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getTypeDefinition()
    {
        if ($this->size === null) {
            return $this->type;
        }

        return $this->type . '(' . $this->size . ')';
    }

    /**
     * @return int|null
     */
    public function getSize()
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

    /**
     * @return bool
     */
    public function isNullable()
    {
        return $this->isNullable;
    }

    /**
     * @return bool
     */
    public function isPk()
    {
        return $this->pk;
    }
    
    /**
     * @return bool
     */
    public function isFk()
    {
        return $this->fk !== null;
    }
}
