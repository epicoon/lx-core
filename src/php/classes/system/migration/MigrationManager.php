<?php

namespace lx;

/**
 * Самое общее, централизованное управление миграциями
 * */
class MigrationManager {
	/**
	 * @var $migrationsChecked array - массив, в котором отмечаются проверенные сервисы на
	 *      накаченные миграции в пределах выполняемой сессии применения всех изменений
	 */
	private $migrationsChecked = [];

	/**
	 * Инициация накатывания всех миграций
	 * */
	public function run($services = null) {
		if ($services === null) {
			$list = PackageBrowser::getServicesList();
			$services = [];
			foreach ($list as $name => $path) {
				$services[] = Service::create($name);
			}
		}

		foreach ($services as $service) {
			$this->runService($service);
		}
	}

	/**
	 * Инициация накатывания всех миграций в конкретном сервисе
	 * */
	public function runService($service) {
		// Проверка существующих миграций - все ли накачены
		$this->checkMigrations($service);

		$info = ModelBrowser::getModelsInfo($service);

		foreach ($info as $modelName => $modelInfo) {
			if ($modelInfo['needTable'] || $modelInfo['hasChanges']) {
				$this->runModel($service, $modelName, $modelInfo['path'], $modelInfo['code']);
			}
		}
	}

	/**
	 * Проверка конкретной модели на измение и если они есть - генерация и накатывание миграций
	 * */
	public function runModel($service, $modelName, $path = null, $code = null) {
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
	}

	/**************************************************************************************************************************
	 * PRIVATE
	 *************************************************************************************************************************/

	/**
	 * Проверка ненакаченных миграций для сервиса
	 * */
	private function checkMigrations($service) {
		if (array_search($service->name, $this->migrationsChecked) !== false) {
			return;
		}
		$this->migrationsChecked[] = $service->name;

		$dir = $service->conductor->getMigrationDirectory();
		if (!$dir->contain('map.json')) {
			return;
		}

		$mapFile = $dir->get('map.json');
		$map = json_decode($mapFile->get(), true);
		$list = $map['list'];
		usort($list, function($a, $b) {
			if ($a['time']===$b['time']) return 0;
			return ($a['time'] < $b['time']) ? -1 : 1;
		});

		foreach ($list as &$migrationRow) {
			if (!$migrationRow['applied']) {
				$migration = json_decode($dir->get($migrationRow['name'])->get(), true);
				ModelMigrateExecutor::runMigration($service, $migration);
				$migrationRow['applied'] = true;
			}
		}
		unset($migrationRow);

		$map['list'] = $list;
		$mapFile->put(json_encode($map));
	}
}
