<?php

namespace lx;

class ModelBrowser extends ApplicationTool {
	private $_modelName;
	private $_service = null;
	private $_path = null;
	private $_code = null;
	private $_schema = null;

	/**
	 * @param $service Service - сервис, для моделей которого строится список
	 * @return ModelBrowser[]
	 * */
	public static function getList($service) {
		$pathes = $service->conductor->getModelsPath();
		$result = [];
		foreach ($pathes as $modelName => $path) {
			$result[$modelName] = new self($service->app, [
				'service' => $service,
				'modelName' => $modelName,
				'path' => $path,
			]);
		}
		return $result;
	}

	/**
	 * Получить аналитическую информацию по всем моделям выбранного сервиса
	 * @param $service Service - сервис, для моделей которого ищется информация
	 * @param $map array - карта необходимых параметров. Если не передана, возвращаются все параметры
	 * Возможные параметры: [path, code, needTable, hasChanges]
	 * @return array - возвращает ассоциативный массив: ключи - имена моделей, значения - запрошенные данные о моделях
	 * */
	public static function getModelsInfo($service, $map = null) {
		if ($map === null) {
			$map = ['path', 'code', 'needTable', 'hasChanges'];
		}
		$pathes = $service->conductor->getModelsPath();

		$result = [];
		foreach ($pathes as $modelName => $path) {
			$file = new File($path);
			$code = $file->get();
			$browser = new self($service->app, [
				'service' => $service,
				'modelName' => $modelName,
				'path' => $path,
				'code' => $code,
			]);

			$data = [];
			if (array_search('path', $map) !== false) {
				$data['path'] = $path;
			}
			if (array_search('code', $map) !== false) {
				$data['code'] = $code;
			}
			if (array_search('needTable', $map) !== false) {
				$data['needTable'] = $browser->needTable();
			}
			if (array_search('hasChanges', $map) !== false) {
				$data['hasChanges'] = $browser->changed();
			}

			$result[$modelName] = $data;
		}

		return $result;
	}

	/**
	 *
	 * */
	public function __construct($app, $config) {
		parent::__construct($app);
		
		//todo Нужно обязательно, чтобы найти - может в перспективе будет связка с классом, который сейчас необязателен
		if (!isset($config['service'])) {
			throw new \Exception("ModelBrowser need service", 1);
		}
		if (!isset($config['modelName']) && !isset($config['path'])) {
			throw new \Exception("ModelBrowser need model name or model path", 1);
		}

		$this->_service = is_string($config['service'])
			? $this->app->getService($config['service'])
			: $config['service'];

		if (isset($config['path'])) {
			$this->_path = $config['path'];
		}
		if (isset($config['modelName'])) {
			$this->_modelName = $config['modelName'];
		}
		if ($this->_path === null) {
			$this->_path = $this->_service->conductor->getModelPath($this->_modelName);
		}

		// Можно вычислить
		if (isset($config['code'])) {
			$this->_code = $config['code'];
		}
	}

	/**
	 *
	 * */
	public function __get($name) {
		switch ($name) {
			case 'path':
				return $this->_path;
			case 'service':
				return $this->_service;

			case 'modelName':
				if ($this->_modelName === null) {
					$schema = $this->schema;
				}
				return $this->_modelName;
			case 'code':
				if ($this->_code === null) {
					$file = new File($this->path);
					$this->_code = $file->get();
				}
				return $this->_code;
			case 'schema':
				if ($this->_schema === null) {
					$file = new YamlFile($this->path);
					$data = $file->get();
					foreach ($data as $key => $value) {
						$this->_modelName = $key;
						$this->_schema = $value;
						return $this->_schema;
					}
				}
				return $this->_schema;
			
			default: return null;
		}
	}

	/**
	 *
	 * */
	public function getFullInfo() {
		return [
			'name' => $this->modelName,
			'service' => $this->service->name,
			'path' => $this->path,
			'code' => $this->code,
			'schema' => $this->schema,
			'needTable' => $this->needTable(),
			'changed' => $this->changed(),
			'needMigrate' => ($this->needTable() || $this->changed()), 
		];
	}

	/**
	 *
	 * */
	public function getEssenceSchema($params = null) {
		$schema = $this->service->modelProvider->getSchema($this->modelName);
		return $schema->getDefinitions($params);
	}

	/**
	 * Определяет нужно ли создать таблицу для модели
	 * */
	public function needTable() {
		$crudAdapter = $this->service->modelProvider->getCrudAdapter();
		if ( ! $crudAdapter) {
			return false;
		}

		return $crudAdapter->checkNeedTable($this->modelName);
	}

	/**
	 * Определяет есть ли директивы для изменений в коде модели
	 * $modelInfo может содержать путь, файл, код
	 * */
	public function changed() {
		/*
		!!change : можно поменять
			- имя таблицы
			- имя поля
			- тип поля
			- дефолтное значение поля
		!!add : можно добавить
			- новое поле (?)
			- новые экземпляры моделей в формате
				!!add:
				  - f0: val1_0
				    f1: val1_1
				  - f0: val2_0
				    f1: val2_1
				или
				!!add:
				  vars:
				    $slug1: SomeModel('slug1').field
				    $slug2: SomeModel('slug2').field + '_' + SomeModel('slug1').field
				  models:
				    - f0: $slug1
				      f1: val1_1
				    - f0: $slug2
				      f1: val2_1
		!!addTable : можно добавить
			- новые экземпляры моделей в формате
				!!addTable: [
				  [ f0,     f1     ]
				  [ val1_0, val1_1 ]
				  [ val2_0, val2_1 ]
				]
				или
				!!add:
				  vars:
				    $slug1: SomeModel('slug1')->field;
				    $slug2: SomeModel('slug2')->field . '_' . SomeModel('slug1')->field;
				  models: [
				    [ f0,     f1     ]
				    [ $slug1, val1_1 ]
				    [ $slug2, val2_1 ]
				  ]
		!!remove : можно удалить
			- поле
			- экземпляры моделей по значениям полей
		!!query : выполнение запроса (видимо, с синтаксисом SQL)
		*/
		return (bool)preg_match('/!!(change|add|edit|remove|query)/', $this->code);
	}
}
