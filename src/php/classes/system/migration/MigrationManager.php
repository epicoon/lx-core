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
				$services[] = \lx::$app->getService($name);
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

	/**
	 * Накатывание конкретной миграции
	 * */
	public function upMigration($service, $migrationName) {
		$dir = $service->conductor->getMigrationDirectory();
		$migrationFile = $dir->get($migrationName . '.json');
		if ( ! $migrationFile) {
			return false;
		}

		$migration = json_decode($migrationFile->get(), true);
		return $this->runMigrationProcess($service, $migrationName, $migration);
	}

	/**
	 * Откатывание конкретной миграции
	 * */
	public function downMigration($service, $migrationName) {
		$dir = $service->conductor->getMigrationDirectory();
		$migrationFile = $dir->get($migrationName . '.json');
		if ( ! $migrationFile) {
			return false;
		}

		$migration = json_decode($migrationFile->get(), true);
		return $this->downMigrationProcess($service, $migrationName, $migration);
	}


	/**************************************************************************************************************************
	 * PRIVATE
	 *************************************************************************************************************************/

	/**
	 * Поиск ненакаченных миграций для сервиса и их выполнение
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
			$migration = json_decode($dir->get($migrationName . '.json')->get(), true);
			$this->runMigrationProcess($service, $migrationName, $migration);
		}
	}

	/**
	 * Алгоритм накатывания конкретной миграции
	 * */
	private function runMigrationProcess($service, $migrationName, $migration) {
		$crudAdapter = $service->modelProvider->getCrudAdapter();
		switch ($migration['type']) {
			case MigrationMaker::TYPE_NEW_TABLE:
				$name = $migration['model'];
				$schema = new ModelSchema($service->modelProvider, $name, $migration['schema']);
				if ( ! $crudAdapter->createTable($name, $schema)) {
					return false;
				}
				break;

			case MigrationMaker::TYPE_DROP_TABLE:
				$name = $migration['model'];
				if ( ! $crudAdapter->deleteTable($name)) {
					return false;
				}
				break;

			case MigrationMaker::TYPE_ALTER_TABLE:
				if ( ! $crudAdapter->correctModel($migration['model'], $migration['actions'])) {
					return false;
				}
				break;

			case MigrationMaker::TYPE_CONTENT_TABLE:
				$name = $migration['model'];
				$schema = new ModelSchema($service->modelProvider, $name, $migration['schema']);
				if ( ! $crudAdapter->correctModelEssences($migration['model'], $migration['actions'], $schema)) {
					return false;
				}
				break;

			case MigrationMaker::TYPE_RELATIONS_TABLE:
				//TODO
				// $migration['model']
				// $migration['relations']
				break;

			case MigrationMaker::TYPE_DELETE_RELATIONS_TABLE:
				//TODO
				// $migration['model']
				// $migration['relations']
				break;
		}

		MigrationMap::getInstance()->up($service->name, $migrationName);
		return true;
	}

	/**
	 * Алгоритм откатывания конкретной миграции
	 * */
	private function downMigrationProcess($service, $migrationName, $migration) {
		$crudAdapter = $service->modelProvider->getCrudAdapter();
		switch ($migration['type']) {
			case MigrationMaker::TYPE_NEW_TABLE:
				$name = $migration['model'];
				if ( ! $crudAdapter->deleteTable($name)) {
					return false;
				}
				break;

			case MigrationMaker::TYPE_DROP_TABLE:
				$name = $migration['model'];
				$schema = new ModelSchema($service->modelProvider, $name, $migration['schema']);
				if ( ! $crudAdapter->createTable($name, $schema)) {
					return false;
				}
				break;

			case MigrationMaker::TYPE_ALTER_TABLE:
				foreach ($migration['actions'] as &$action) {
					switch ($action['action']) {
						case ModelMigrateExecutor::ACTION_ADD_FIELD:
							$action['action'] = ModelMigrateExecutor::ACTION_REMOVE_FIELD;
							break;

						case ModelMigrateExecutor::ACTION_REMOVE_FIELD:
							$action['action'] = ModelMigrateExecutor::ACTION_ADD_FIELD;
							break;

						case ModelMigrateExecutor::ACTION_RENAME_FIELD:
							$old = $action['old'];
							$action['old'] = $action['new'];
							$action['new'] = $old;
							break;

						// ModelMigrateExecutor::ACTION_CHANGE_FIELD_PROPERTY
					}
				}
				unset($action);
				if ( ! $crudAdapter->correctModel($migration['model'], $migration['actions'])) {
					return false;
				}
				break;

			case MigrationMaker::TYPE_CONTENT_TABLE:
				foreach ($migration['actions'] as &$action) {
					switch ($action[0]) {
						case 'add':
							$action[0] = 'del';
							break;
						
						case 'del':
							$action[0] = 'add';
							break;

						case 'edit':
							foreach ($action[1] as &$pare) {
								$old = $pare[1];
								$pare[1] = $pare[0];
								$pare[0] = $old;
							}
							unset($pare);
							break;

						case 'query':
							//TODO пока не решено. Есть идея делать два поля up: и down: при написании запроса в yaml-файле. Если нет down - миграция неоткатываемая
							return false;
							break;
					}
				}
				unset($action);
				$name = $migration['model'];
				$schema = new ModelSchema($service->modelProvider, $name, $migration['schema']);
				if ( ! $crudAdapter->correctModelEssences($migration['model'], $migration['actions'], $schema)) {
					return false;
				}
				break;

			case MigrationMaker::TYPE_RELATIONS_TABLE:
				//TODO
				// $migration['model']
				// $migration['relations']
				break;

			case MigrationMaker::TYPE_DELETE_RELATIONS_TABLE:
				//TODO
				// $migration['model']
				// $migration['relations']
				break;
		}

		MigrationMap::getInstance()->down($service->name, $migrationName);
		return true;
	}
}
