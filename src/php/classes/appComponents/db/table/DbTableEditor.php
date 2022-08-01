<?php

namespace lx;

class DbTableEditor
{
    private ?DbConnectionInterface $db;
    private ?DbTableSchema $schema;

    public function __construct(?DbConnectionInterface $db = null, ?DbTableSchema $schema = null)
    {
        $this->db = $db;
        if ($db && $schema && $schema->getDb() !== null && $schema->getDb() !== $db) {
            //TODO а если в этой базе уже есть такая таблица с совершенно другой структурой?
            $this->schema = clone $schema;
            $this->schema->setDb($this->db);
        } else {
            $this->schema = $schema;
        }
    }
    
    public static function createTableFromConfig(DbConnectionInterface $db, array $config, bool $rewrite = false): bool
    {
        $dbSchema = DbTableSchema::createByConfig($config);
        $editor = new self($db, $dbSchema);
        return $editor->createTable($rewrite);
    }
    
    public function setDb(DbConnectionInterface $db)
    {
        $this->db = $db;
        if ($this->schema && $this->schema->getDb() !== null && $this->schema->getDb() !== $db) {
            $this->schema = null;
        }
    }
    
    public function setTableSchema(DbTableSchema $schema)
    {
        $this->schema = $schema;
        if ($schema->getDb()) {
            $this->db = $schema->getDb();
        }
    }
    
    public function loadTableSchema(DbConnectionInterface $db, string $tableName)
    {
        $this->db = $db;
        $this->schema = $db->getTableSchema($tableName);
    }
    
    public function getDb(): ?DbConnectionInterface
    {
        return $this->db;
    }
    
    public function getTableSchema(): ?DbTableSchema
    {
        return $this->schema;
    }

    public function createTable(bool $rewrite = false): bool
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

        $query = $db->getQueryBuilder()->getCreateTableQuery($this->schema);
        $result = $db->query($query);
        if (!$result) {
            $db->transactionRollback();
            return false;
        }

        $db->transactionCommit();
        return true;
    }

    public function dropTable(): bool
    {
        if (!$this->schema) {
            return false;
        }

        $db = $this->getDb();
        $db->transactionBegin();

        foreach ($this->schema->getForeignKeysInfo() as $fk) {
            $query = $db->getQueryBuilder()->getDropForeignKeyQuery(
                $fk->getTableName(),
                $fk->getFieldNames(),
                $fk->getName()
            );
            $result = $db->query($query);
            if (!$result) {
                $db->transactionRollback();
                return false;
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

    public function addField(array $definition): bool
    {
        $field = $this->schema->addField($definition);

        $db = $this->getDb();
        $db->transactionBegin();

        $query = $db->getQueryBuilder()->getAddColumnQuery($this->schema->getName(), $field);
        $result = $db->query($query);
        if (!$result) {
            $db->transactionRollback();
            return false;
        }

        if ($field->isFk()) {
            $fk = $field->getForeignKeyInfo();
            $query = $db->getQueryBuilder()->getAddForeignKeyQuery(
                $fk->getTableName(),
                $fk->getFieldNames(),
                $fk->getRelatedTableName(),
                $fk->getRelatedFieldNames(),
                $fk->getName()
            );
            $result = $db->query($query);
            if (!$result) {
                $db->transactionRollback();
                return false;
            }
        }

        $db->transactionCommit();
        return true;
    }

    public function delField(string $fieldName): bool
    {
        $db = $this->getDb();
        $db->transactionBegin();

        $field = $this->schema->getField($fieldName);
        if ($field->isFk()) {
            $fk = $field->getForeignKeyInfo();
            $query = $db->getQueryBuilder()->getDropForeignKeyQuery(
                $fk->getTableName(),
                $fk->getFieldNames(),
                $fk->getName()
            );
            $result = $db->query($query);
            if (!$result) {
                $db->transactionRollback();
                return false;
            }
        }

        $query = $db->getQueryBuilder()->getDelColumnQuery($this->schema->getName(), $fieldName);
        $result = $db->query($query);
        if (!$result) {
            $db->transactionRollback();
            return false;
        }

        $db->transactionCommit();
        return true;
    }

    public function renameField(string $oldFieldName, string $newFieldName): bool
    {
        $db = $this->getDb();
        $query = $db->getQueryBuilder()->getRenameColumnQuery($this->schema->getName(), $oldFieldName, $newFieldName);
        return $db->query($query);
    }

    public function changeField(array $newDefinition): bool
    {
        $field = $this->schema->addField($newDefinition);
        $db = $this->getDb();
        $query = $db->getQueryBuilder()->getChangeColumnQuery($this->schema->getName(), $field);
        return $db->query($query);
    }
}
