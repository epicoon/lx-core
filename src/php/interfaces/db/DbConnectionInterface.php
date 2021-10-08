<?php

namespace lx;

interface DbConnectionInterface extends FlightRecorderHolderInterface
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
    //TODO DbTable нужен интерфейс?
    public function getTable(string $name): DbTable;

    /**
     * @return mixed
     */
    public function query(string $query);
    public function massUpdate(string $tableName, array $rows): bool;

    //TODO DbTableSchema нужен интерфейс?
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

    //TODO - метод с претензией на private, в данный момент публичный, т.к. используется в DbTable для сборки запросов
    // если сборку запросов перенести в базу, то и метод можно будет сделать приватным
    /**
     * @param mixed $value
     */
    public function convertValueForQuery($value): string;
}
