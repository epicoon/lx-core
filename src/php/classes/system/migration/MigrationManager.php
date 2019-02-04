<?php

namespace lx;

/**
 * Самое общее, централизованное управление миграциями
 * */
class MigrationManager {
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
		$info = (new ModelBrowser($service))->getModelsInfo();

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
		if (!$modelMigrater->run()) {
			throw new \Exception("Migration failed for model '$modelName' in service '{$service->mame}'", 400);
		}
	}

	/**************************************************************************************************************************
	 * PRIVATE
	 *************************************************************************************************************************/
}
