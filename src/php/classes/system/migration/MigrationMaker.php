<?php

namespace lx;

/**
 * Класс для создания файлов миграций
 * */
class MigrationMaker {
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
	public function createTableMigration($modelName, $tableName) {
		$migrationData = [
			'type' => 'table_create',
			'model' => $modelName,
			'schema' => $this->service->modelProvider->getSchemaArray($modelName),
		];
		$namePrefix = 'm_new_table';
		$this->saveMigration($tableName, $namePrefix, $migrationData);
	}

	/**
	 * Сгенерировать миграции, отметить их выполненными
	 * Для ситуации удаления таблицы модели
	 * */
	public function deleteTableMigration($modelName, $tableName) {
		$migrationData = [
			'type' => 'table_delete',
			'model' => $modelName,
			'table_name' => $tableName,
		];
		$namePrefix = 'm_del_table';
		$this->saveMigration($tableName, $namePrefix, $migrationData);
	}

	/**
	 * Сгенерировать миграции, отметить их выполненными
	 * Для ситуаций корректировки схемы модели
	 * */
	public function createInnerMigration($modelName, $tableName, $innerActions) {
		$migrationData = [
			'type' => 'table_alter',
			'model' => $modelName,
			'table_name' => $tableName,
			'actions' => $innerActions,
		];
		$namePrefix = 'm_alter_table';
		$this->saveMigration($tableName, $namePrefix, $migrationData);
	}

	/**
	 * Сгенерировать миграции, отметить их выполненными
	 * Для ситуаций добавления новых экземпляров моделей
	 * */
	public function createOuterMigration($modelName, $tableName, $outerActions) {
		$migrationData = [
			'type' => 'table_content',
			'model' => $modelName,
			'table_name' => $tableName,
			'actions' => $outerActions,
		];
		$namePrefix = 'm_content_table';
		$this->saveMigration($tableName, $namePrefix, $migrationData);
	}

	/**
	 * Создание файла миграции
	 * */
	private function saveMigration($tableName, $namePrefix, $migrationData) {
		$time = explode(' ', microtime());
		$time = $time[1] . '_' . $time[0];
		$migrationFileName = $namePrefix .'__'. $tableName .'_' . $time . '.json';

		$dir = $this->service->conductor->getMigrationDirectory();
		$file = $dir->makeFile($migrationFileName);
		$code = json_encode($migrationData);
		$file->put($code);

		$mapFile = $dir->contain('map.json')
			? $dir->get('map.json')
			: $dir->makeFile('map.json');
		$map = $mapFile->exists()
			? json_decode($mapFile->get(), true)
			: ['list' => []];
		$map['list'][] = [
			'time' => $time,
			'name' => $migrationFileName,
		];
		$mapFile->put(json_encode($map));

		MigrationMap::getInstance()->up($this->service->name, $migrationFileName);
	}
}
