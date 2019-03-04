<?php

namespace lx;

class DataObject {
	protected
		$nullCash = null,
		$_prop = [];

	public static function create($arr=[]) {
		if ($arr instanceof DataObject) return $arr;

		$obj = new self();
		if (is_array($arr)) $obj->setProperties($arr);
		return $obj;
	}

	public function __set($prop, $val) {
		if (property_exists($this, $prop)) {
			$this->$prop = $val;
			return;
		}

		$this->_prop[$prop] = $val;
	}

	public function &__get($prop) {
		if (property_exists($this, $prop))
			return $this->$prop;
		if (array_key_exists($prop, $this->_prop))
			return $this->_prop[$prop];
		$this->nullCash = null;
		return $this->nullCash;
	}

	/*
	 * $full - полное имя класса с пространством имен, если false - только собственное имя класса
	 * */
	public static function className($full=true) {
		$name = get_called_class();
		if ($full) return $name;
		$array = explode('\\', $name);
		return $array[count($array) - 1];
	}

	/**
	 * Пространство имен класса
	 * */
	public static function namespaceName() {
		$staticClass = static::class;
		$namespace = preg_replace('/\\\[^\\'.'\]+$/', '', $staticClass);
		return $namespace;
	}

	/**
	 * Возвращает динамические свойства
	 * */
	public function getProperties() {
		return $this->_prop;
	}

	/**
	 * Инициализировать динамические свойства переданным массивом
	 * */
	public function setProperties($arr) {
		$this->_prop = $arr;
	}

	/**
	 * Имеются ли динамические свойства у объекта
	 * */
	public function hasProperties() {
		return count($this->_prop) > 0;
	}

	/**
	 * Проверяет, что поле с именем $name существует в динамических свойствах и равно null
	 * */
	public function isNull($name) {
		return (array_key_exists($name, $this->_prop) && $this->_prop[$name] === null);
	}

	/**
	 * Вернет динамическое свойство, при этом удалит его из объекта
	 * */
	public function extract($name) {
		if (!array_key_exists($name, $this->_prop)) return null;
		$res = $this->_prop[$name];
		unset($this->_prop[$name]);
		return $res;
	}

	/**
	 * Объект считается пустым, если у него нет динамических свойств
	 * */
	public function isEmpty() {
		return count($this->_prop) === 0;
	}

	/**
	 * $names - массив наименований полей (статических и динамических), из которых первое существующее в объекте будет возвращено
	 * если нет таких полей, будет возвращено значение по умолчанию
	 * */
	public function getFirstDefined($names, $default=null) {
		if (is_string($names)) $names = [$names];
		foreach ($names as $name) {
			if (array_key_exists($name, $this->_prop)) return $this->_prop[$name];
			if (property_exists($this, $name)) return $this->$name;
		}
		return $default;
	}

	/**
	 * Проверить наличие собственного свойства
	 * */
	public function hasOwnProperty($name) {
		return property_exists($this, $name);
	}

	/**
	 * Проверить наличие динамического свойства (в массиве $_prop)
	 * */
	public function hasDynamicProperty($name) {
		return array_key_exists($name, $this->_prop);
	}

	/**
	 * Проверить наличие свойства (хоть собственного, хоть динамического)
	 * */
	public function hasProperty($name) {
		return (
			property_exists($this, $name)
			||
			array_key_exists($name, $this->_prop)
		);
	}

	/**
	 * Проверить на наличие свойства и соответствие значению
	 * */
	public function testProperty($name, $val) {
		if (!$this->hasProperty($name)) return false;
		return $this->$name == $val;
	}

	/**
	 * Проверить на наличие метода
	 * */
	public function hasMethod($name) {
		require method_exists($this, $name);
	}
}
