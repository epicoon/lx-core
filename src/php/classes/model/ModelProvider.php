<?php

namespace lx;

class ModelProvider extends ApplicationTool {
	private $service;
	private $defaultCrudAdapter = null;
	// Карта соответствий моделей и CRUD-адаптеров
	private $crudMap = [];

	private $managers = [];
	private $schemas = [];

	public function __construct($service, $crudAdapter = null) {
		parent::__construct($service->app);
		$this->service = $service;

		if ($crudAdapter) {
			$this->setCrudAdapter($crudAdapter);
		}
	}


	/*************************************************************************************************************************
	 *	PUBLIC
	 *************************************************************************************************************************/

	/**
	 * Можно установить какой CRUD-адаптер будет использоваться для моделей
	 * Можно передать имя модели, или массив имен - для каких именно моделей он будет использоваться
	 * */
	public function setCrudAdapter($crudAdapter, $modelName = null) {
		$crudAdapter->setModelProvider($this);

		if (is_string($modelName)) {
			$this->crudMap[$modelName] = $crudAdapter;
		} elseif (is_array($modelName)) {
			foreach ($modelName as $name) {
				$this->crudAdapter[$name] = $crudAdapter;
			}
		} else {
			$this->defaultCrudAdapter = $crudAdapter;
		}
	}

	/**
	 *
	 * */
	public function getCrudAdapter($modelName = null) {
		return array_key_exists($modelName, $this->crudMap)
			? $this->crudMap[$modelName]
			: $this->defaultCrudAdapter;
	}

	/**
	 * Получить модуль, к которому относится данный провайдер
	 * */
	public function getService() {
		return $this->service;
	}

	/**
	 * Получить менеджер модели
	 * */
	public function getManager($modelName) {
		if ( ! is_string($modelName)) {
			return null;
		}

		if (!array_key_exists($modelName, $this->managers)) {
			$schema = $this->getSchema($modelName);
			if (!$schema) {
				return null;
			}

			$this->managers[$modelName] = new ModelManager($this->app, $this->getCrudAdapter($modelName), $schema);
		}

		return $this->managers[$modelName];
	}

	/**
	 * Получить схему модели
	 * */
	public function getSchema($modelName) {
		if ( ! array_key_exists($modelName, $this->schemas)) {
			if ( ! $this->loadSchema($modelName)) {
				return null;
			}
		}

		return $this->schemas[$modelName];
	}

	/**
	 * Получить массив схемы
	 * */
	public function getSchemaArray($modelName) {
		$path = $this->getSchemaPath($modelName);
		$file = new YamlFile($path);
		if ( ! $file->exists()) {
			return false;
		}

		$data = $file->get();
		return $data[$modelName];
	}

	/**
	 * Путь к конкретной модели
	 * */
	public function getSchemaPath($modelName) {
		return $this->service->conductor->getModelPath($modelName);
	}

	/**
	 * Массив путей ко всем моделям
	 * */
	public function getSchemasPath() {
		return $this->service->conductor->getModelsPath();
	}















	//TODO - работа с таблицами уходит глубоко к CRUD адаптеру, может они и тут не нужны
	//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	// Эти методы нужны для управления миграциями - надо там напрямую с CRUD-адаптером работать

	/**
	 *
	 * */
	public function createRelationTables($modelName, $schema = null) {
		if ($this->checkModelNeedTable($modelName)) {
			return false;
		}

		$adapter = $this->getCrudAdapter($modelName);
		if ($schema === null) {
			$schema = $this->getSchema($modelName);
		}

		$result = [];
		foreach ($schema->getRelations() as $name => $relation) {
			$relativeModelName = $relation->getRelativeModelName();
			if ($this->checkModelNeedTable($relativeModelName)) {
				continue;
			}

			$relativeSchema = $relation->getRelativeSchema();
			if ($adapter->checkNeedRelationTable($schema, $relativeSchema)) {
				if ($adapter->createRelationTable($schema, $relativeSchema)) {
					$result[] = [$schema->getName(), $relativeSchema->getName()];
				}
			}
		}

		if (empty($result)) {
			return false;
		}

		return $result;
	}


























	/**
	 * Создает файл модели с базовым кодом. НЕ создает таблиц через CRUD-адаптер
	 * */
	public function createModel($modelName) {
		$path = $this->service->conductor->getDefaultModelPath() . '/' . $modelName . '.yaml';
		$code = $modelName . ':' . PHP_EOL
				. '  fields:' . PHP_EOL
				. '    name: {type: string}' . PHP_EOL;

		$file = new File($path);
		$file->put($code);

		return true;
	}

	/**
	 * Удаляет модель полностью - и файл с описанием модели, и таблицы через CRUD-адаптер
	 * */
	public function deleteModel($modelName) {
		$adapter = $this->getCrudAdapter($modelName);
		$adapter->deleteTable($modelName);

		$path = $this->getSchemaPath($modelName);
		$file = new \lx\File($path);
		$file->remove();

		unset($this->schemas[$modelName]);

		return true;
	}


	/*************************************************************************************************************************
	 *	PRIVATE
	 *************************************************************************************************************************/

	/**
	 *
	 * */
	private function loadSchema($modelName) {
		if (array_key_exists($modelName, $this->schemas)) {
			throw new \Exception("Model named $modelName is already used", 400);
		}

		$schemaArray = $this->getSchemaArray($modelName);
		if ( ! $schemaArray) {
			return false;
		}

		$this->schemas[$modelName] = new ModelSchema($this, $modelName, $schemaArray);
		return true;
	}
}
