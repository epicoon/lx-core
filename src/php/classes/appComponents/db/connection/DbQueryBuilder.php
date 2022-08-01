<?php

namespace lx;

abstract class DbQueryBuilder implements DbQueryBuilderInterface
{
    use FlightRecorderHolderTrait;

    private ?DbConnectionInterface $connection = null;

    public function setConnection(DbConnectionInterface $connection): void
    {
        $this->connection = $connection;
    }

    public function getConnection(): ?DbConnectionInterface
    {
        return $this->connection;
    }

    abstract public function getCreateTableQuery(DbTableSchema $schema): string;
    /**
     * @param string|array $fields
     * @param string|array $relFields
     */
    abstract public function getAddForeignKeyQuery(
        string $table, $fields,
        string $relTable, $relFields,
        ?string $constraintName = null
    ): string;
    /**
     * @param string|array $fields
     */
    abstract public function getDropForeignKeyQuery(
        string $table, $fields,
        ?string $constraintName = null
    ): string;
    abstract public function getAddColumnQuery(string $tableName, DbTableField $field): string;
    abstract public function getDelColumnQuery(string $tableName, string $fieldName): string;
    abstract public function getChangeColumnQuery(string $tableName, DbTableField $field): string;
    abstract public function getRenameColumnQuery(string $tableName, string $oldFieldName, string $newFieldName): string;

    /**
     * @param string|array $fields
     * @param array|string|int|null $condition
     */
    abstract public function getSelectQuery(string $tableName, $fields = '*', $condition = null): ?string;
    /**
     * @param array|string|int|null $condition
     */
    abstract public function getInsertQuery(string $tableName, array $fields, $values = null): ?string;
    /**
     * @param array|string|int|null $condition
     */
    abstract public function getUpdateQuery(string $tableName, array $sets, $condition = null): ?string;
    abstract public function getMassUpdateQuery(string $tableName, array $rows): ?string;
    /**
     * @param array|string|int|null $condition
     */
    abstract public function getDeleteQuery(string $tableName, $condition = null): ?string;
    abstract public function convertValueForQuery($value): string;
}
