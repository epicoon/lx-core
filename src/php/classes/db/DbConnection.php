<?php

namespace lx;

abstract class DbConnection implements DbConnectionInterface
{
    use ErrorCollectorTrait;

    const SELECT_TYPE_BOTH = 1;
	const SELECT_TYPE_ASSOC = 2;
	const SELECT_TYPE_NUM = 3;

	protected array $settings;
	/** @var resource|null */
    protected $connection;

    public function __construct(array $settings)
    {
        $this->settings = $settings;
        $this->connection = null;
    }

    abstract public function connect(): bool;
    abstract public function disconnect(): bool;
    abstract public function getTableSchema(string $tableName): ?DbTableSchema;
    /**
     * @param array|string|null $fields
     */
    abstract public function getContrForeignKeysInfo(string $tableName, $fields = null): array;

    abstract public function getTableName(string $name): string;
    abstract public function tableExists(string $name): bool;
    abstract public function renameTable(string $oldName, string $newName): bool;

    /**
     * @return mixed
     */
    abstract public function query(string $query);
    abstract public function massUpdate(string $tableName, array $rows): bool;

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
    abstract public function getRenameColumnQuery(
        string $tableName,
        string $oldFieldName,
        string $newFieldName
    ): string;

    public function transactionBegin(): void
    {
        $this->query('BEGIN;');
    }

    public function transactionRollback(): void
    {
        $this->query('ROLLBACK;');
    }

    public function transactionCommit(): void
    {
        $this->query('COMMIT;');
    }
    
	public function dropTable(string $name): bool
    {
		$name = $this->getTableName($name);
		return $this->query("DROP TABLE IF EXISTS $name");
	}

	public function getTable(string $name): DbTable
    {
		$name = $this->getTableName($name);
		return new DbTable($this, $name);
	}


	








	/**
     * TODO перенести все методы-запросы из DbTable в DbConnection, тогда этот метод станет protected
     * 
	 * Приводит значение к формату, подходящему для текста запроса
	 */
	public static function valueForQuery($value) {
		if (is_string($value)) return "'$value'";
		if (is_bool($value)) return $value ? 'TRUE' : 'FALSE';
		if (is_null($value)) return 'NULL';
		return $value;
	}
}
