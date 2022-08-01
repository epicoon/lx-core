<?php

namespace lx;

class DbTableSchema
{
    private ?DbConnectionInterface $db;
    private string $name;
    /** @var array<DbTableField> */
    private array $fields;
    /** @var array<string> */
    private array $pkNames;
    private array $fks;

    /**
     * Config example:
     * [
     *     'name' => 'table_name',
     *     'fields' => [
     *         'id' => [
     *             'pk' => true,
     *             'type' => \lx\DbTableField::TYPE_SERIAL,
     *         ],
     *         'price' => [
     *              'type' => \lx\DbTableField::TYPE_DECIMAL,
     *              'details' => [
     *                  'precision' => 10,
     *                  'scale' => 2,
     *              ],
     *         ],
     *         'field_fk' => [
     *             'type' => \lx\DbTableField::TYPE_INTEGER,
     *             'fk' => [
     *                 'table' => 'rel_table_name',
     *                 'field' => 'rel_field_name',
     *             ],
     *         ],
     *         'field_fk_part_1' => [
     *             'type' => \lx\DbTableField::TYPE_INTEGER,
     *         ],
     *         'field_fk_part_2' => [
     *             'type' => \lx\DbTableField::TYPE_INTEGER,
     *         ],
     *     ],
     *     'fk' => [
     *     [
     *         'fields' => ['field_fk_part_1', 'field_fk_part_2'],
     *         'relatedTable' => 'rel_table_name',
     *         'relatedFields' => ['rel_field_1', 'rel_field_2'],
     *         ]
     *     ],
     * ]
     */
    public static function createByConfig(array $config): DbTableSchema
    {
        return new self($config);
    }

    public static function createByTableName(DbConnectionInterface $db, string $tableName): ?DbTableSchema
    {
        return $db->getTableSchema($tableName);
    }

    private function __construct(array $config)
    {
        $this->db = null;
        $this->name = $config['name'];
        $this->pkNames = [];

        $this->fields = [];
        foreach (($config['fields'] ?? []) as $fieldName => $fieldDefinition) {
            if (is_string($fieldName)) {
                $fieldDefinition['name'] = $fieldName;
            }
            $field = new DbTableField($this, $fieldDefinition);
            $this->fields[$fieldName] = $field;
            if ($field->isPk()) {
                $this->pkNames[] = $field->getName();
            }
        }
        
        $this->fks = $config['fk'] ?? [];
    }
    
    public function setDb(DbConnectionInterface $db): void
    {
        $this->db = $db;
    }

    public function getDb(): ?DbConnectionInterface
    {
        return $this->db;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPKNames(): array
    {
        return $this->pkNames;
    }

    /**
     * @return array<DbTableField>
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    public function getField(string $name): ?DbTableField
    {
        return $this->fields[$name] ?? null;
    }
    
    public function hasField($name): bool
    {
        return array_key_exists($name, $this->fields);
    }

    public function addField(array $definition = []): DbTableField
    {
        $field = new DbTableField($this, $definition);
        $this->fields[$field->getName()] = $field;
        if ($field->isPk() && !in_array($field->getName(), $this->pkNames)) {
            $this->pkNames[] = $field->getName();
        }
        return $field;
    }

    public function delField(string $fieldName): void
    {
        unset($this->fields[$fieldName]);
    }

    /**
     * @return array<DbForeignKeyInfo>
     */
    public function getForeignKeysInfo(): array
    {
        $result = [];
        foreach ($this->fks as $fk) {
            $fk['table'] = $this->getName();
            $result[] = new DbForeignKeyInfo($this, $fk);
        }
        foreach ($this->getFields() as $field) {
            if ($field->isFk()) {
                $result[] = $field->getForeignKeyInfo();
            }
        }
        return $result;
    }

    /**
     * @return array<DbForeignKeyInfo>
     */
    public function getContrForeignKeysInfo(?array $fieldNames = null): array
    {
        $info = $this->getDb()->getContrForeignKeysInfo($this->getName(), $fieldNames);
        $result = [];
        foreach ($info as $fk) {
            $result[] = new DbForeignKeyInfo($this, $fk);
        }
        return $result;
    }
}
