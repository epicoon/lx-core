<?php

namespace lx;

class ModelProvider {
	private $service;
	private $defaultCrudAdapter = null;
	// Карта соответствий моделей и CRUD-адаптеров
	private $crudMap = [];

	private $managers = [];
	private $schemas = [];

	public function __construct($service, $crudAdapter = null) {
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
	 * Получить модуль, к которому относится данный провайдер
	 * */
	public function getService() {
		return $this->service;
	}

	/**
	 * Получить менеджер модели
	 * */
	public function getManager($modelName) {
		if (!is_string($modelName)) return null;

		if (!array_key_exists($modelName, $this->managers)) {
			$schema = $this->getSchema($modelName);
			if (!$schema) {
				return null;
			}
			$this->managers[$modelName] = new ModelManager($this->getCrudAdapter($modelName), $schema);
		}

		return $this->managers[$modelName];
	}

	/**
	 * Получить схему модели
	 * */
	public function getSchema($modelName) {
		if (!array_key_exists($modelName, $this->schemas)) {
			if (!$this->loadSchema($modelName)) {
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
		if (!$file->exists()) {
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

	/**
	 * Проверить - существует ли для модели таблица
	 * */
	public function checkModelNeedTable($modelName) {
		$adapter = $this->getCrudAdapter($modelName);
		// Если адаптера нет - значит модели не поддерживают CRUD-интерфейс, таблицы им не нужны
		if (!$adapter) {
			return false;
		}

		$schema = $this->getSchema($modelName);

		return $adapter->checkNeedTable($schema);
	}

	/**
	 *
	 * */
	public function createTable($modelName, $schema = null) {
		$adapter = $this->getCrudAdapter($modelName);
		if ($schema === null) {
			$schema = $this->getSchema($modelName);
		}
		return $adapter->createTable($schema);
	}

	/**
	 * Директивы для внесения изменений в структуре данных CRUD-адаптером для модели
	 * */
	public function correctModel($modelName, $tableName, $actions) {
		$adapter = $this->getCrudAdapter($modelName);
		return $adapter->correctModel($modelName, $tableName, $actions);
	}

	/**
	 *
	 * */
	public function addModelEssences($modelName, $tableName, $actions) {
		$adapter = $this->getCrudAdapter($modelName);
		return $adapter->addModelEssences($modelName, $tableName, $actions);
	}


	/*************************************************************************************************************************
	 *	PRIVATE
	 *************************************************************************************************************************/

	/**
	 *
	 * */
	private function getCrudAdapter($modelName) {
		return array_key_exists($modelName, $this->crudMap)
			? $this->crudMap[$modelName]
			: $this->defaultCrudAdapter;
	}

	/**
	 *
	 * */
	private function loadSchema($modelName) {
		if (array_key_exists($modelName, $this->schemas)) {
			throw new \Exception("Model named $modelName is already used", 400);
		}

		$this->schemas[$modelName] = new ModelSchema($modelName, $this->getSchemaArray($modelName));
		return true;
	}
}
