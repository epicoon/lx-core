<?php

namespace lx;

abstract class Model {
	private static $nullCache;

	protected static $_service = null;
	protected static $_manager = null;
	protected static $_name = null;

	protected $data = null;

	public function __construct($data = null) {
		if ($data === null) {
			$manager = self::manager();
			if ($manager) {
				$this->data = $manager->newModel();
			}
		} else {
			//todo if ($data->getManager() !== self::manager()) ...

			$this->data = $data;
		}
	}

	public function &__get($prop) {
		if ($this->data === null) {
			return $this->null();
		}

		if ($this->data->hasField($prop)) return $this->data->$prop;
		//todo свои поля?
	}

	public function __set($prop, $val) {
		if ($this->data === null) {
			return;
		}

		if ($this->data->hasField($prop)) $this->data->$prop = $val;
		//todo свои поля?
	}

	public function setData($data) {
		$this->data = $data;
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
		if (static::$_service === null) {
			$serviceName = ClassHelper::defineService(static::class);
			if ($serviceName) {
				static::$_service = Service::create($serviceName);
			}
		}
		return static::$_service;
	}

	public static function setService($service) {
		static::$_service = $service;
	}

	public static function name() {
		if (static::$_name === null) {
			if (defined(static::class . '::MODEL_NAME')) {
				static::$_name = static::MODEL_NAME;
			}
		}

		return static::$_name;
	}

	public static function setName($name) {
		static::$_name = $name;
	}

	public static function manager() {
		if (static::$_manager === null) {
			$service = self::service();
			$name = self::name();
			if ($service && $name) {
				static::$_manager = $service->modelProvider->getManager(self::name());
			}
		}

		return static::$_manager;
	}

	/**
	 *
	 * */
	protected function & null() {
		self::$nullCache = null;
		return self::$nullCache;
	}

	private static function getForModelsData($models) {
		$result = [];
		foreach ($models as $model) {
			$result[] = new static($model);
		}
		return $result;
	}
}
