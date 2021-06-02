<?php

namespace lx;

interface DbConnectionInterface extends ErrorCollectorInterface
{
    public function __construct(array $settings);
    public function connect(): bool;
    public function disconnect(): bool;

    //TODO DbTableSchema нужен интерфейс?
    public function getTableSchema(string $tableName): ?DbTableSchema;
    /**
     * @param array|string|null $fields
     */
    public function getContrForeignKeysInfo(string $tableName, $fields = null): array;

    public function transactionBegin(): void;
    public function transactionRollback(): void;
    public function transactionCommit(): void;

    public function getTableName(string $name): string;
    public function tableExists(string $name): bool;
    public function renameTable(string $oldName, string $newName): bool;
    public function dropTable(string $name): bool;
    public function getTable(string $name): DbTable;

    /**
     * @return mixed
     */
    public function query(string $query);
    public function massUpdate(string $tableName, array $rows): bool;

    public function getCreateTableQuery(DbTableSchema $schema): string;
    /**
     * @param string|array $fields
     * @param string|array $relFields
     */
    public function getAddForeignKeyQuery(
        string $table, $fields,
        string $relTable, $relFields,
        ?string $constraintName = null
    ): string;
    /**
     * @param string|array $fields
     */
    public function getDropForeignKeyQuery(
        string $table, $fields,
        ?string $constraintName = null
    ): string;
    public function getAddColumnQuery(string $tableName, DbTableField $field): string;
    public function getDelColumnQuery(string $tableName, string $fieldName): string;
    public function getChangeColumnQuery(string $tableName, DbTableField $field): string;
    public function getRenameColumnQuery(string $tableName, string $oldFieldName, string $newFieldName): string;
}
