<?php

namespace lx;

abstract class DB {
	const POSTGRESQL = 'pgsql';
	const MYSQL = 'mysql';

	const SELECT_TYPE_FULL = 5;    // Возвращать все найденные поля
	const SELECT_TYPE_MAP = 10;    // Возвращать поля с ключами именами полей
	const SELECT_TYPE_ARRAY = 15;  // Возвращать поля с числовыми ключами

	const SHORT_SCHEMA = 20;

	const TYPE_INTEGER = 'integer';
	const TYPE_STRING = 'string';
	const TYPE_BOOLEAN = 'boolean';

	public static $connections = null;

	public $selectType;

	protected
		$settings,
		$connection = null,
		$error = null;

	public static function create($settings) {
		if (self::$connections === null) {
			self::$connections = new DbConnectionList();
		}

		list($type, $connection) = self::$connections->add($settings);
		switch ($type) {
            case DB::POSTGRESQL: return new DBpostgres($settings, $connection);
			case DB::MYSQL: return new DBmysql($settings, $connection);
			case false: return null;
		}
	}

	public function __construct($settings, $connection) {
		$this->settings = $settings;
		$this->connection = $connection;
	}

    abstract public function getTableSchema($tableName);
	abstract public function getContrForeignKeysInfo($tableName);

	//TODO отвязать от $schema
    abstract public function getCreateTableQuery($schema);
    abstract public function getAddColumnQuery($schema, $fieldName);
    abstract public function getDelColumnQuery($schema, $fieldName);
    abstract public function getChangeColumnQuery($schema, $fieldName);
    abstract public function getRenameColumnQuery($schema, $oldFieldName, $newFieldName);
    abstract public function getAddForeignKeyQuery(
        string $table, string $field,
        string $relTable, string $relField,
        ?string $constraintName = null
    ): string;
    abstract public function getDropForeignKeyQuery(
        string $table, string $field,
        ?string $constraintName = null
    ): string;

	abstract public function query($query);
	abstract public function select($query);
	abstract public function insert($query, $returnId);
	abstract public function massUpdate($tableName, $rows);
	abstract public function tableExists($name);  // Проверка существования таблицы
	abstract public function renameTable($oldName, $newName);
	abstract public function tableName($name);

    //TODO deprecated
    abstract public function tableSchema($name, $fields=null);  // Схема таблицы

	public function getName() {
		return $this->settings['dbName'];
	}

	public function getConnection() {
		return $this->connection;
	}

	/**
	 * Проверить есть ли ошибки
	 * */
	public function hasError() {
		return $this->error !== null;
	}

	/**
	 * Получить текст ошибки, если есть
	 * @return string|null
	 * */
	public function getError() {
		return $this->error;
	}

	public function connect() {
		if ($this->connection !== null) return;

		list($type, $connection) = self::$connections->add($this->settings);
		$this->connection = $connection;
	}

	/**
	 * Закрытие соединения
	 * */
	public function close() {
		if ($this->connection === null) return;

		self::$connection->drop($this->settings);
		$this->connection = null;
	}

    /**
     * @return void
     */
	public function transactionBegin()
    {
        $this->query('BEGIN;');
    }

    /**
     * @return void
     */
    public function transactionRollback()
    {
        $this->query('ROLLBACK;');
    }

    /**
     * @return void
     */
    public function transactionCommit()
    {
        $this->query('COMMIT;');
    }

	/**
	 * Удаление таблицы
	 * */
	public function dropTable($name) {
		$name = $this->tableName($name);
		return $this->query("DROP TABLE IF EXISTS $name");
	}

	/**
	 * Получить объектное представление таблицы базы данных
	 * */
	public function table($name) {
		$name = $this->tableName($name);
		return new DbTable($name, $this);
	}

	/**
	 * Проверяет пустая ли таблица
	 * */
	public function tableIsEmpty($name) {
		$name = $this->tableName($name);
		$res = $this->query("SELECT * FROM $name LIMIT 1;");
		return empty($res);
	}

	/**
	 * В зависимости от особенностей загрузки различных типов разными базами - надо переопределять у конкретных баз
	 * */
	public function normalizeTypes($table, $rows) {
		return $rows;
	}

	/**
	 * Приводит значение к формату, подходящему для текста запроса
	 * */
	public static function valueForQuery($value) {
		if (is_string($value)) return "'$value'";
		if (is_bool($value)) return $value ? 'TRUE' : 'FALSE';
		if (is_null($value)) return 'NULL';
		return $value;
	}

	/**
	 * Форматирования найденных данных - выбрасываются числовые ключи, либо текстовые, либо остаются все
	 * */
	protected function applySelectType($row, $selectType) {
		if ($selectType == self::SELECT_TYPE_MAP) {
			foreach ($row as $key => $value) {
				if (is_numeric($key)) unset($row[$key]);
			}
		} else if ($selectType == self::SELECT_TYPE_ARRAY) {
			foreach ($row as $key => $value) {
				if (is_string($key)) unset($row[$key]);
			}
		}
		return $row;
	}
}
