<?php

namespace lx;

abstract class Model {
	private static $_service = null;
	private static $_manager = null;

	protected $data;

	public function __construct($data = null) {
		if ($data === null) {
			$manager = self::manager();
			$this->data = $manager->newModel();
		} else {
			//todo if ($data->getManager() !== self::manager()) ...

			$this->data = $data;
		}
	}

	public function &__get($prop) {
		if ($this->data->hasField($prop)) return $this->data->$prop;
		//todo свои поля?
	}

	public function __set($prop, $val) {
		if ($this->data->hasField($prop)) $this->data->$prop = $val;
		//todo свои поля?
	}

	public function getData() {
		return $this->data;
	}

	public static function getNew($count = 1) {
		$models = self::manager()->newModels($count);
		return self::getForModelsData($models);
	}

	public static function load($condition = null) {
		$models = self::manager()->loadModels($condition);
		return self::getForModelsData($models);
	}

	public static function loadOne($condition = null) {
		$model = self::manager()->loadModel($condition);
		return new static($model);
	}

	public static function saveAll($arr) {
		$models = [];
		foreach ($arr as $adapter) {
			$models[] = $adapter->getData();
		}
		self::manager()->saveModels($models);
	}

	public static function deleteAll($arr) {
		$models = [];
		foreach ($arr as $adapter) {
			$models[] = $adapter->getData();
		}
		self::manager()->deleteModels($models);
	}

	public function getFields()        { return $this->data->getFields();     }
	public function hasField($name)    { return $this->data->hasField($name); }
	public function isNew()            { return $this->data->isNew();         }
	public function isChanged()        { return $this->data->isChanged();     }
	public function setFields($fields) { $this->data->setFields($fields);     }
	public function save()             { $this->data->save();                 }
	public function delete()           { $this->data->delete();               }
	public function reset()            { $this->data->reset();                }
	public function drop()             { $this->data->drop();                 }

	public static function service() {
		if (self::$_service === null) {
			$serviceName = ClassHelper::defineService(static::class);
			if ($serviceName) {
				self::$_service = Service::create($serviceName);
			}
		}
		return self::$_service;
	}

	public static function manager() {
		if (self::$_manager === null) {
			$service = self::service();
			self::$_manager = $service->modelProvider->getManager(static::MODEL_NAME);
		}
		return self::$_manager;
	}

	private static function getForModelsData($models) {
		$result = [];
		foreach ($models as $model) {
			$result[] = new static($model);
		}
		return $result;
	}
}
