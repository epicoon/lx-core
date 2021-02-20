<?php

namespace lx;

/**
 * Class DbTableSchema
 * @package lx
 */
class DbTableSchema
{
    /** @var DB */
    private $db;

    /** @var string */
    private $name;

    /** @var DbTableField[] */
    private $fields;

    /** @var array */
    private $pkNames;

    /**
     * @param Db $db
     * @param array $config
     * @return DbTableSchema
     */
    public static function createByConfig($db, $config)
    {
        return new self($db, $config);
    }

    /**
     * @param DB $db
     * @param string $tableName
     * @return DbTableSchema|null
     */
    public static function createByTableName($db, $tableName)
    {
        return $db->getTableSchema($tableName);
    }

    /**
     * @param Db $db
     * @param array $config
     */
    private function __construct($db, $config)
    {
        $this->db = $db;
        $this->name = $config['name'];
        $this->pkNames = [];

        $this->fields = [];
        foreach (($config['fields'] ?? []) as $fieldName => $fieldDefinition) {
            if (is_string($fieldName)) {
                $fieldDefinition['name'] = $fieldName;
            }
            $this->fields[$fieldName] = new DbTableField($this, $fieldDefinition);
        }
    }

    /**
     * @return DB
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getPKNames()
    {
        return $this->pkNames;
    }

    /**
     * @return DbTableField[]
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param string $name
     * @return DbTableField|null
     */
    public function getField($name)
    {
        return $this->fields[$name] ?? null;
    }

    /**
     * @param array $definition
     * @return DbTableField
     */
    public function addField($definition = [])
    {
        $field = new DbTableField($this, $definition);
        $this->fields[$field->getName()] = $field;
        return $field;
    }

    /**
     * @param string $fieldName
     */
    public function delField($fieldName)
    {
        unset($this->fields[$fieldName]);
    }

    /**
     * @param string|string[] $name
     */
    public function setPK($name)
    {
        $this->pkNames = (array)$name;
    }

    /**
     * @return array
     */
    public function getContrForeignKeysInfo()
    {
        return $this->getDb()->getContrForeignKeysInfo($this->getName());
    }
}
