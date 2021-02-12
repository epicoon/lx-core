<?php

namespace lx;

/**
 * Class DbTableBuilder
 * @package lx
 */
class DbTableBuilder
{
    /** @var DbTableSchema */
    private $schema;

    /**
     * DbTableBuilder constructor.
     * @param DbTableSchema|null $schema
     */
    public function __construct($schema = null)
    {
        $this->schema = $schema;
    }

    /**
     * @return DB|null
     */
    public function getDb()
    {
        return $this->schema ? $this->schema->getDb() : null;
    }

    /**
     * @param DbTableSchema $schema
     */
    public function setSchema($schema)
    {
        $this->schema = $schema;
    }

    /**
     * @return DbTableSchema|null
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * @param bool $rewrite
     * @return bool
     */
    public function createTable($rewrite = false)
    {
        if (!$this->schema) {
            return false;
        }

        $db = $this->getDb();
        $name = $this->schema->getName();

        $db->transactionBegin();
        if ($db->tableExists($name)) {
            if ($rewrite) {
                $db->dropTable($name);
            } else {
                $db->transactionRollback();
                return false;
            }
        }

        $query = $db->getCreateTableQuery($this->schema);
        $result = $db->query($query);
        if (!$result) {
            $db->transactionRollback();
            return false;
        }

        foreach ($this->schema->getFields() as $field) {
            if ($field->isFk()) {
                $fk = $field->getDefinition()['fk'];
                $query = $db->getAddForeignKeyQuery(
                    $this->schema->getName(),
                    $field->getName(),
                    $fk['table'],
                    $fk['field']
                );
                $result = $db->query($query);
                if (!$result) {
                    $db->transactionRollback();
                    return false;
                }
            }
        }

        $db->transactionCommit();
        return true;
    }

    /**
     * @return bool
     */
    public function dropTable()
    {
        if (!$this->schema) {
            return false;
        }

        $db = $this->getDb();
        $db->transactionBegin();

        foreach ($this->schema->getFields() as $field) {
            if ($field->isFk()) {
                $fk = $field->getDefinition()['fk'];
                $query = $db->getDropForeignKeyQuery(
                    $this->schema->getName(),
                    $field->getName(),
                    $fk['name'] ?? null
                );
                $result = $db->query($query);
                if (!$result) {
                    $db->transactionRollback();
                    return false;
                }
            }
        }

        $result = $db->dropTable($this->schema->getName());
        if (!$result) {
            $db->transactionRollback();
            return false;
        }

        $db->transactionCommit();
        return true;
    }

    /**
     * @param array $definition
     * @return bool
     */
    public function addField($definition)
    {
        $field = $this->schema->addField($definition);
        $db = $this->getDb();
        $query = $db->getAddColumnQuery($this->schema, $field->getName());
        return $db->query($query);
    }

    /**
     * @param string $fieldName
     * @return bool
     */
    public function delField($fieldName)
    {
        $db = $this->getDb();
        $query = $db->getDelColumnQuery($this->schema, $fieldName);
        return $db->query($query);
    }

    /**
     * @param string $oldFieldName
     * @param string $newFieldName
     * @return bool
     */
    public function renameField($oldFieldName, $newFieldName)
    {
        $db = $this->getDb();
        $query = $db->getRenameColumnQuery($this->schema, $oldFieldName, $newFieldName);
        return $db->query($query);
    }

    /**
     * @param array $newDefinition
     * @return bool
     */
    public function changeField($newDefinition)
    {
        $field = $this->schema->addField($newDefinition);
        $db = $this->getDb();
        $query = $db->getChangeColumnQuery($this->schema, $field->getName());
        return $db->query($query);
    }
}
