<?php

namespace lx;

/**
 * Класс для создания файлов миграций
 * Class MigrationMaker
 * @package lx
 */
class MigrationMaker
{
	const TYPE_NEW_TABLE = 'new_table';
	const TYPE_DROP_TABLE = 'del_table';
	const TYPE_ALTER_TABLE = 'alter_table';
	const TYPE_CONTENT_TABLE = 'content_table';
	const TYPE_RELATIONS_TABLE = 'relations_table';
	const TYPE_DELETE_RELATIONS_TABLE = 'del_relations_table';

	/** @var Service */
	private $service;

	/**
	 * MigrationMaker constructor.
	 * @param $service Service
	 */
	public function __construct($service)
	{
		$this->service = $service;
	}

	/**
	 * Сгенерировать миграции, отметить их выполненными
	 * Для ситуации создания таблицы модели
	 *
	 * @param $modelName string
	 */
	public function createTableMigration($modelName)
	{
		$this->saveMigration($modelName, self::TYPE_NEW_TABLE, [
			'model' => $modelName,
			'schema' => $this->service->modelProvider->getSchemaArray($modelName),
		]);
	}

	/**
	 * Сгенерировать миграции, отметить их выполненными
	 * Для ситуации удаления таблицы модели
	 *
	 * @param $modelName string
	 */
	public function deleteTableMigration($modelName)
	{
		$this->saveMigration($modelName, self::TYPE_DROP_TABLE, [
			'model' => $modelName,
			'schema' => $this->service->modelProvider->getSchemaArray($modelName),
		]);
	}

	/**
	 * Сгенерировать миграции, отметить их выполненными
	 * Для ситуаций корректировки схемы модели
	 *
	 * @param $modelName string
	 * @param $innerActions array
	 */
	public function createInnerMigration($modelName, $innerActions)
	{
		$this->saveMigration($modelName, self::TYPE_ALTER_TABLE, [
			'model' => $modelName,
			'actions' => $innerActions,
		]);
	}

	/**
	 * Сгенерировать миграции, отметить их выполненными
	 * Для ситуаций добавления новых экземпляров моделей
	 *
	 * @param $modelName string
	 * @param $outerActions array
	 */
	public function createOuterMigration($modelName, $outerActions)
	{
		$this->saveMigration($modelName, self::TYPE_CONTENT_TABLE, [
			'model' => $modelName,
			'schema' => $this->service->modelProvider->getSchemaArray($modelName),
			'actions' => $outerActions,
		]);
	}

	/**
	 * Сгенерировать миграции, отметить их выполненными
	 * Для ситуаций добавления новых связей модели
	 *
	 * @param $modelName string
	 * @param $relationTableNames array
	 */
	public function createRelationTablesMigration($modelName, $relationsData)
	{
		$this->saveMigration($modelName, self::TYPE_RELATIONS_TABLE, [
			'model' => $modelName,
			'relations' => $relationsData,
		]);
	}

	/**
	 * Сгенерировать миграции, отметить их выполненными
	 * Для ситуаций добавления новых связей модели
	 *
	 * @param $modelName string
	 * @param $relationTableNames array
	 */
	public function deleteRelationTablesMigration($modelName, $relationsData)
	{
		$this->saveMigration($modelName, self::TYPE_DELETE_RELATIONS_TABLE, [
			'model' => $modelName,
			'relations' => $relationsData,
		]);
	}

	/**
	 * Создание файла миграции
	 *
	 * @param $modelName string
	 * @param $type int
	 * @param $migrationData array
	 */
	private function saveMigration($modelName, $type, $migrationData)
	{
		$time = explode(' ', microtime());
		$time = $time[1] . '_' . $time[0];
		$migrationName = 'm__' . $time . '__' . $modelName . '_' . $type;
		$migrationFileName = $migrationName . '.json';

		$dir = $this->service->conductor->getMigrationDirectory();
		$file = $dir->makeFile($migrationFileName);
		$code = json_encode(array_merge(['type'=>$type], $migrationData));
		$file->put($code);

		MigrationMap::getInstance()->up($this->service->name, $migrationName);
	}
}
