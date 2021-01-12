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

    /** @var boolean */
    private $isNullable;

    /** @var mixed */
    private $defailt;

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
        $this->isNullable = $definition['nullable'] ?? true;
        $this->defailt = $definition['default'] ?? null;
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
     * @param DbTableField $field
     * @return boolean
     */
    public function hasEqualDefinition($field)
    {
        return ($this->getType() === $field->getType()
            && $this->getSize() === $field->getSize()
            && $this->getDefault() === $field->getDefault()
            && $this->isNullable() === $field->isNullable()
        );
    }
}
