<?php

namespace lx;

use lx;

abstract class DbConnection implements DbConnectionInterface
{
    use FlightRecorderHolderTrait;

    const SELECT_TYPE_BOTH = 1;
	const SELECT_TYPE_ASSOC = 2;
	const SELECT_TYPE_NUM = 3;

	protected array $settings;
	/** @var mixed|null */
    protected $connection;

    public function __construct(array $settings)
    {
        $this->settings = $settings;
        $this->connection = null;
    }

    public function getDriver(): string
    {
        return $this->settings['driver'];
    }

    public function getQueryBuilder(): DbQueryBuilderInterface
    {
        $builder = lx::$app->dbConnector->getConnectionFactory()->getQueryBuilder($this->getDriver());
        $builder->setConnection($this);
        return $builder;
    }

    abstract public function connect(): bool;
    abstract public function disconnect(): bool;

    abstract public function getTableSchema(string $tableName): ?DbTableSchema;
    abstract public function getContrForeignKeysInfo(string $tableName, ?array $fields = null): array;
    abstract public function getTableName(string $name): string;
    abstract public function tableExists(string $name): bool;
    abstract public function renameTable(string $oldName, string $newName): bool;
    /**
     * @return mixed
     */
    abstract public function query(string $query);

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
}
