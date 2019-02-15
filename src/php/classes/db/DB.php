<?php

namespace lx;

abstract class DB {
	const
		SELECT_TYPE_FULL = 5,    // Возвращать все найденные поля
		SELECT_TYPE_MAP = 10,    // Возвращать поля с ключами именами полей
		SELECT_TYPE_ARRAY = 15,  // Возвращать поля с числовыми ключами

		SHORT_SCHEMA = 20,

		TYPE_INTEGER = 'integer',
		TYPE_STRING = 'string',
		TYPE_BOOLEAN = 'boolean';

	public
		$selectType;

	protected
		$hostname = '',
		$username = '',
		$password = '',
		$dbName = '',
		$connect = null;

	public static function create($settings=null) {
		if ($settings === null) {
			$settings = require(Conductor::$lx . '/config/db.php');		
		}
		if (isset($settings['db'])) {
			return self::createProc(strtolower($settings['db']) , $settings);
		}

		$arr = ['postgresql', 'mysql'];
		foreach ($arr as $value) {
			$db = self::createProc($value, $settings);
			if (!$db->connect()) continue;
			$db->close();
			return $db;
		}
	}

	public function __construct($settings = null) {
		$this->hostname = $settings['hostname'];
		$this->username = $settings['username'];
		$this->password = $settings['password'];
		$this->dbName = $settings['dbName'];
	}

	abstract public function connect();
	abstract public function close();
	abstract public function query($query);
	abstract public function select($query);
	abstract public function insert($query, $returnId);
	abstract public function massUpdate($table, $rows);
	abstract public function tableExists($name);  // Проверка существования таблицы
	abstract public function tableSchema($name, $fields=null);  // Схема таблицы
	abstract public function renameTable($oldName, $newName);
	abstract public function tableRenameColumn($tableName, $oldName, $newName);
	abstract public function tableAddColumn($tableName, $name, $definition);
	abstract public function tableDropColumn($tableName, $name);

	public function getName() {
		return $this->dbName;
	}

	/**
	 * Создание новой таблицы
	 * @param $name - имя новой таблицы
	 * @param $column - массив DbColumnDefinition
	 * @param $rewrite - флаг, показывающий можно ли пересоздать таблицу, если таблица с таким именем уже существует
	 * */
	public function newTable($name, $columns, $rewrite=false) {
		if ($rewrite) {
			$this->query("DROP TABLE IF EXISTS $name");
		} else {
			if ($this->tableExists($name))
				return false;
		}

		$query = "CREATE TABLE $name (";
		$cols = [];
		foreach ($columns as $colName => $definition) {
			$str = $this->definitionToString($definition);
			$str = str_replace('#key#', $colName, $str);
			$cols[] = "$colName $str";
		}
		$cols = implode(', ', $cols);
		$cols = str_replace('#pkey#', $name, $cols);
		$query .= "$cols);";

		$res = $this->query($query);
		return $res;
	}

	/**
	 * Удаление таблицы
	 * */
	public function dropTable($name) {
		return $this->query("DROP TABLE IF EXISTS $name");
	}

	/**
	 * Получить объектное представление таблицы базы данных
	 * */
	public function table($name) {
		return new DbTable($name, $this);
	}

	/**
	 * Сформировать новый DbColumnDefinition по строковому обозначению типа
	 * */
	public function type($conf) {
		if (is_string($conf)) $conf = ['type' => $conf];
		return new DbColumnDefinition($conf);
	}

	/**
	 * Сформировать новый DbColumnDefinition для первичного ключа
	 * */
	public function primaryKeyDefinition($conf=[]) {
		$conf['type'] = '';
		$conf['isPK'] = true;
		return new DbColumnDefinition($conf);
	}

	/**
	 * Сформировать новый DbColumnDefinition для целого числа
	 * */
	public function integer($conf=[]) {
		$size = 0;
		if (is_numeric($conf)) {
			$size = $conf;
			$conf = [];
		} else if (isset($conf['size'])) {
			$size = $conf['size'];
			unset($conf['size']);
		}
		switch ($size) {
			case 2:
				$conf['type'] = 'smallint';
				break;
			case 8:
				$conf['type'] = 'bigint';
				break;
			default:
				$conf['type'] = 'integer';
				break;
		}
		return new DbColumnDefinition($conf);
	}

	/**
	 * Сформировать новый DbColumnDefinition для текста
	 * */
	public function varchar($conf=[]) {
		if (is_numeric($conf)) $conf = ['size' => $conf];
		$conf['type'] = 'varchar';
		if (!isset($conf['size'])) $conf['size'] = 255;
		return new DbColumnDefinition($conf);
	}

	/**
	 * Сформировать новый DbColumnDefinition для булева поля
	 * */
	public function boolean($conf=[]) {
		if (is_bool($conf)) {
			if ($conf) $conf = ['default' => 'true'];
			else $conf = ['default' => 'false'];
		} else if ($conf == 'true' || $conf == 'false') {
			$conf = ['default' => $conf];
		}
		$conf['type'] = 'boolean';
		return new DbColumnDefinition($conf);
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

	/**
	 * Процедура создания экземпляра базы
	 * */
	private static function createProc($type, $settings) {
		switch ($type) {
			case 'postgresql': return new DBpostgres($settings);
			case 'mysql': return new DBmysql($settings);
		}
	}

	abstract protected function definitionToString($definition);
}
