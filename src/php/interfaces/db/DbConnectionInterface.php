<?php

namespace lx;

interface DbConnectionInterface extends FlightRecorderHolderInterface
{
    public function __construct(array $settings);
    public function getDriver(): string;
    public function getQueryBuilder(): DbQueryBuilderInterface;

    public function connect(): bool;
    public function disconnect(): bool;

    //TODO DbTableSchema нужен интерфейс?
    public function getTableSchema(string $tableName): ?DbTableSchema;
    public function getContrForeignKeysInfo(string $tableName, ?array $fields = null): array;

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
}
