<?php

namespace lx;

class MysqlQueryBuilder extends DbQueryBuilder
{
    public function getCreateTableQuery(DbTableSchema $schema): string
    {

    }

    /**
     * @param string|array $fields
     * @param string|array $relFields
     */
    public function getAddForeignKeyQuery(
        string  $table, $fields,
        string  $relTable, $relFields,
        ?string $constraintName = null
    ): string
    {

    }

    /**
     * @param string|array $fields
     */
    public function getDropForeignKeyQuery(
        string  $table, $fields,
        ?string $constraintName = null
    ): string
    {

    }

    public function getAddColumnQuery(string $tableName, DbTableField $field): string
    {

    }

    public function getDelColumnQuery(string $tableName, string $fieldName): string
    {

    }

    public function getChangeColumnQuery(string $tableName, DbTableField $field): string
    {

    }

    public function getRenameColumnQuery(string $tableName, string $oldFieldName, string $newFieldName): string
    {

    }

    /**
     * @param string|array $fields
     * @param array|string|int|null $condition
     */
    public function getSelectQuery(string $tableName, $fields = '*', $condition = null): ?string
    {

    }

    /**
     * @param array|string|int|null $condition
     */
    public function getInsertQuery(string $tableName, array $fields, $values = null): ?string
    {

    }

    /**
     * @param array|string|int|null $condition
     */
    public function getUpdateQuery(string $tableName, array $sets, $condition = null): ?string
    {

    }

    public function getMassUpdateQuery(string $tableName, array $rows): ?string
    {

    }

    /**
     * @param array|string|int|null $condition
     */
    public function getDeleteQuery(string $tableName, $condition = null): ?string
    {

    }

    public function convertValueForQuery($value): string
    {

    }
}
