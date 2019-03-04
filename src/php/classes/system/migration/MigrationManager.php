<?php

namespace lx;

/**
 * Класс для:
 * 1. Накатывания новых миграций
 * 2. Поиска директив для изменения моделей со стороны сервера:
 * - отсутствие таблиц для моделей
 * - наличие директив в yaml-схемах моделей
 * 3. Выполнение этих директив и создание для них миграций
 * */
class MigrationManager {
	/**
	 * @var $migrationsChecked array - массив, в котором отмечаются проверенные сервисы на
	 *      накаченные миграции в пределах выполняемой сессии применения всех изменений
	 */
	private $migrationsChecked = [];

	/**
	 * 1. Поиск и выполнение существующих ненакаченных миграций
	 * 2. Поиск директив для изменения моделей
	 * 3. Выполнение этих директив
	 * 4. Создание соответствующих миграций
	 * - делается по всему приложению
	 * */
	public function run($services = null) {
		if ($services === null) {
			$list = PackageBrowser::getServicesList();
			$services = [];
			foreach ($list as $name => $path) {
				$services[] = Service::create($name);
			}
		}

		MigrationMap::getInstance()->open();
		foreach ($services as $service) {
			$this->runService($service);
		}
		MigrationMap::getInstance()->close();
	}

	/**
	 * 1. Поиск и выполнение существующих ненакаченных миграций
	 * 2. Поиск директив для изменения моделей
	 * 3. Выполнение этих директив
	 * 4. Создание соответствующих миграций
	 * - делается по выбранному сервису
	 * */
	public function runService($service) {
		MigrationMap::getInstance()->open();

		// Проверка существующих миграций - все ли накачены
		$this->checkMigrations($service);

		$info = ModelBrowser::getModelsInfo($service);

		foreach ($info as $modelName => $modelInfo) {
			if ($modelInfo['needTable'] || $modelInfo['hasChanges']) {
				$this->runModel($service, $modelName, $modelInfo['path'], $modelInfo['code']);
			}
		}
		MigrationMap::getInstance()->close();
	}

	/**
	 * 1. Поиск и выполнение существующих ненакаченных миграций
	 * 2. Поиск директив для изменения модели
	 * 3. Выполнение этих директив
	 * 4. Создание соответствующих миграций
	 * - делается по выбранной модели
	 * */
	public function runModel($service, $modelName, $path = null, $code = null) {
		MigrationMap::getInstance()->open();

		// Проверка существующих миграций - все ли накачены
		$this->checkMigrations($service);

		if ($path === null) {
			$path = $service->conductor->getModelPath($modelName);
		}
		if ($code === null) {
			$code = (new File($path))->get();
		}

		$file = new YamlFile($path);
		$modelData = $file->get();
		$model = $modelData[$modelName];
		// Остаются команды добавления/удаления/запросов
		unset($modelData[$modelName]);

		$modelMigrater = new ModelMigrateExecutor($service, $modelName, $model, $modelData, $path, $code);
		if (!$modelMigrater->runParseCode()) {
			throw new \Exception("Migration failed for model '$modelName' in service '{$service->mame}'", 400);
		}

		MigrationMap::getInstance()->close();
	}

	/**************************************************************************************************************************
	 * PRIVATE
	 *************************************************************************************************************************/

	/**
	 * Поиск ненакаченных миграций для сервиса и выполнение
	 * */
	private function checkMigrations($service) {
		if (array_search($service->name, $this->migrationsChecked) !== false) {
			return;
		}
		$this->migrationsChecked[] = $service->name;

		$migrationMap = new ServiceMigrationMap($service);
		$list = $migrationMap->getUnappliedList();
		if (empty($list)) {
			return;
		}

		$dir = $service->conductor->getMigrationDirectory();
		foreach ($list as $migrationName) {
			$migration = json_decode($dir->get($migrationName)->get(), true);
			$this->runMigrationProcess($service, $migrationName, $migration);
		}
	}

	/**
	 * Накатывание конкретной миграции
	 * */
	private function runMigrationProcess($service, $migrationName, $migration) {
		$modelProvider = $service->modelProvider;
		switch ($migration['type']) {
			case 'table_create':
				$name = $migration['model'];
				$schema = new ModelSchema($name, $migration['schema']);
				if (!$modelProvider->createTable($name, $schema)) {
					return false;
				}
				break;
			case 'table_delete':
				$name = $migration['model'];
				if (!$modelProvider->deleteTable($name)) {
					return false;
				}
				break;
			case 'table_alter':
				$correctResult = $modelProvider->correctModel(
					$migration['model'],
					$migration['table_name'],
					$migration['actions']
				);
				if (!$correctResult) {
					return false;
				}
				break;
			case 'table_content':
				$correctResult = $modelProvider->correctModelEssences(
					$migration['model'],
					$migration['table_name'],
					$migration['actions']
				);
				if (!$correctResult) {
					return false;
				}
				break;
		}

		MigrationMap::getInstance()->up($service->name, $migrationName);
		return true;
	}
}
