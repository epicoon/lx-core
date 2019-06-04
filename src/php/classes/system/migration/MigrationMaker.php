<?php

namespace lx;

/**
 * Класс для создания файлов миграций
 * */
class MigrationMaker {
	const TYPE_NEW_TABLE = 'new_table';
	const TYPE_DROP_TABLE = 'del_table';
	const TYPE_ALTER_TABLE = 'alter_table';
	const TYPE_CONTENT_TABLE = 'content_table';

	private $service;

	/**
	 *
	 * */
	public function __construct($service) {
		$this->service = $service;
	}

	/**
	 * Сгенерировать миграции, отметить их выполненными
	 * Для ситуации создания таблицы модели
	 * */
	public function createTableMigration($modelName) {
		$migrationData = [
			'type' => 'table_create',
			'model' => $modelName,
			'schema' => $this->service->modelProvider->getSchemaArray($modelName),
		];
		$this->saveMigration($modelName, self::TYPE_NEW_TABLE, $migrationData);
	}

	/**
	 * Сгенерировать миграции, отметить их выполненными
	 * Для ситуации удаления таблицы модели
	 * */
	public function deleteTableMigration($modelName) {
		$migrationData = [
			'type' => 'table_delete',
			'model' => $modelName,
			'schema' => $this->service->modelProvider->getSchemaArray($modelName),
		];
		$this->saveMigration($modelName, self::TYPE_DROP_TABLE, $migrationData);
	}

	/**
	 * Сгенерировать миграции, отметить их выполненными
	 * Для ситуаций корректировки схемы модели
	 * */
	public function createInnerMigration($modelName, $innerActions) {
		$migrationData = [
			'type' => 'table_alter',
			'model' => $modelName,
			'actions' => $innerActions,
		];
		$this->saveMigration($modelName, self::TYPE_ALTER_TABLE, $migrationData);
	}

	/**
	 * Сгенерировать миграции, отметить их выполненными
	 * Для ситуаций добавления новых экземпляров моделей
	 * */
	public function createOuterMigration($modelName, $outerActions) {
		$migrationData = [
			'type' => 'table_content',
			'model' => $modelName,
			'schema' => $this->service->modelProvider->getSchemaArray($modelName),
			'actions' => $outerActions,
		];
		$this->saveMigration($modelName, self::TYPE_CONTENT_TABLE, $migrationData);
	}

	/**
	 * Создание файла миграции
	 * */
	private function saveMigration($modelName, $type, $migrationData) {
		$time = explode(' ', microtime());
		$time = $time[1] . '_' . $time[0];
		$migrationName = 'm__' . $time . '__' . $modelName . '_' . $type;
		$migrationFileName = $migrationName . '.json';

		$dir = $this->service->conductor->getMigrationDirectory();
		$file = $dir->makeFile($migrationFileName);
		$code = json_encode($migrationData);
		$file->put($code);

		MigrationMap::getInstance()->up($this->service->name, $migrationName);
	}
}
