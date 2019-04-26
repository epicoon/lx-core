<?php

namespace lx;

class ModelData {
	private static $nullCache = null;

	protected $manager;
	protected
		$_prop = [],
		$_oldProp = [];
		

	/**
	 *
	 * */
	protected function __construct($manager, $props) {
		$this->manager = $manager;
		$this->_prop = $props;
	}

	/**
	 *
	 * */
	public static function getNew($manager, $count = 1) {
		$schema = $manager->getSchema();
		$props = [];

		$fields = $schema->fieldNames();
		foreach ($fields as $fieldName) {
			$field = $schema->field($fieldName);
			$props[$fieldName] = $field->getDefault();
		}

		if ($count == 1) return new self($manager, $props);;

		$result = [];
		for ($i=0; $i<$count; $i++) {
			$result[] = new self($manager, $props);
		}

		return $result;
	}

	/**
	 *
	 * */
	public static function load($manager, $condition = null) {
		$data = $manager->loadModelsData($condition);
		if (empty($data)) return [];

		$result = [];
		foreach ($data as $props) {
			$obj = new self($manager, $props);
			$result[] = $obj;
		}

		return $result;
	}

	/**
	 *
	 * */
	public static function loadOne($manager, $condition = null) {
		$result = self::load($manager, $condition);
		if (empty($result)) return null;
		return $result[0];
	}

	/**
	 *
	 * */
	public function __set($prop, $val) {
		if (!array_key_exists($prop, $this->_prop)) {
			//todo возможно полезно выкинуть исключение, что не то пытаются инициализировать
			return;
		}

		if ($this->_prop[$prop] === $val) return;


		// Если меняем первичный ключ
		if ($prop == $this->pkName()) {
			// Если занулили первичный ключ - эта ситуация обработается обычным методом
			if ($val === null) {
				$this->setPk(null);
			// Если поменяли первичный ключ - подгрузим запись с этим ключом, значения полей которой станут старыми значениями
			} else {
				//todo - код старый, и вообще спорный. 

				// $val = $this->normalizeType($prop, $val);
				// $temp = self::loadOne($this->table, $val);
				// if ($temp) {
				// 	$this->_prop[$prop] = $val;
				// 	$fields = $temp->getFields();
				// 	foreach ($fields as $key => $value) {
				// 		if ($value != $this->_prop[$key]) {
				// 			$this->_oldProp[$key] = $value;
				// 		}
				// 	}
				// }
			}
			return;
		}


		$field = $this->getSchema()->field($prop);
		if ($val === null && $field->isNotNull()) {
			throw new \Exception("Model set error: property '$prop' can not be 'null'", 400);
		}

		// Для остальных полей следим за соответствием старым значениям
		if (!$this->isNew()) {
			if (array_key_exists($prop, $this->_oldProp)) {
				if ($this->_oldProp[$prop] === $val) unset($this->_oldProp[$prop]);
			} else {
				$this->_oldProp[$prop] = $this->_prop[$prop];
			}
		}

		$this->_prop[$prop] = $this->normalizeType($prop, $val);
	}

	/**
	 *
	 * */
	public function &__get($prop) {
		if (array_key_exists($prop, $this->_prop))
			return $this->_prop[$prop];

		return $this->null();
	}

	/**
	 *
	 * */
	public function pk() {
		return $this->{$this->pkName()};
	}

	/**
	 *
	 * */
	public function pkName() {
		return $this->getSchema()->pkName();
	}

	/**
	 * Метод только для действительно важных моментов - когда персональный ключ, например, получен из других запросов
	 * */
	public function setPk($pk) {
		$pkName = $this->pkName();
		if (!$pkName) return;

		if ($pk === null) {
			$this->manager->uncacheModel($this);
			$this->_prop[$pkName] = null;
			$this->forgetOld();
			return;
		}

		$this->_prop[$pkName] = $this->normalizeType($pkName, $pk);
	}

	/**
	 *
	 * */
	public function getManager() {
		return $this->manager;
	}

	/**
	 *
	 * */
	public function getSchema() {
		return $this->manager->getSchema();
	}

	/**
	 *
	 * */
	public function fieldNames() {
		return array_keys($this->_prop);
	}

	/**
	 *
	 * */
	public function setFields($fields) {
		foreach ($fields as $key => $value) {
			if (array_key_exists($key, $this->_prop)) {
				$this->$key = $value;
			}
		}
	}

	/**
	 *
	 * */
	public function getFields($keys = null) {
		if ($keys === null) return $this->_prop;

		$result = [];
		foreach ($keys as $key) {
			if (array_key_exists($key, $this->_prop)) {
				$result[$key] = $this->_prop[$key];
			}
		}

		return $result;
	}

	/**
	 *
	 * */
	public function hasField($name) {
		return array_key_exists($name, $this->_prop);
	}

	/**
	 *
	 * */
	public function isNew() {
		$pkName = $this->pkName();
		if ($pkName === null) return null;
		return $this->$pkName === null;
	}

	/**
	 *
	 * */
	public function isChanged() {
		return (!empty($this->_oldProp));
	}

	/**
	 *
	 * */
	public function reset() {
		foreach ($this->_oldProp as $key => $value) {
			$this->_prop[$key] = $this->_oldProp[$key];
		}
		$this->_oldProp = [];
	}

	/**
	 * При сохранении старые значения сбрасываются, новые считаются текущими, т.к. соответствуют сохраненному состоянию
	 * */
	public function save() {
		$this->manager->saveModel($this);
		$this->forgetOld();
	}

	/**
	 *
	 * */
	public function delete() {
		$this->manager->deleteModel($this);
		$this->drop();
	}

	/**
	 * Скинуть связь с таблицей - первичный ключ зануляется, все старые значения забываются
	 * */
	public function drop() {
		$this->{$this->pkName()} = null;
		$this->forgetOld();
	}

	/**
	 * Забыть старые значения. Используется менеджером, преимущественно системный метод. Не рекомендуется использовать
	 * */
	public function forgetOld() {
		$this->_oldProp = [];
	}

	/**
	 *
	 * */
	protected function initProps($arr) {
		$this->_prop = $arr;
	}

	/**
	 *
	 * */
	protected function & null() {
		self::$nullCache = null;
		return self::$nullCache;
	}

	/**
	 *
	 * */
	private function normalizeType($prop, $val) {
		$field = $this->getSchema()->field($prop);

		try {
			$value = $field->normalizeValue($val);
		} catch (\Exception $e) {
			throw new \Exception("Model type miscast for property '$prop' by value '$val'", 400);
		}

		return $value;
	}
}
