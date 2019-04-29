<?php

namespace lx;

class ClassHelper {
	/**
	 * Для имени класса определить - является ли он наследником или непосредственно проверяемым классом
	 * */
	public static function checkInstance($currentClass, $baseClass) {
		return ($currentClass == $baseClass || is_subclass_of($currentClass, $baseClass));
	}

	/**
	 * Проверяет существование класса в проекте
	 * */
	public static function exists($className) {
		$autoloader = Autoloader::getInstance();
		try {
			if (class_exists($className)) return true;
			return $autoloader->getClassPath($className) !== false;
		} catch (\Exception $e) {
			return $autoloader->getClassPath($className) !== false;
		}
	}

	/**
	 * Получить пространство имен для класса или объекта
	 * */
	public static function getNamespace($obj) {
		$reflected = new \ReflectionClass($obj);
		if (!$reflected->inNamespace()) {
			return '';
		}

		return $reflected->getNamespaceName();
	}

	/**
	 * Разделяет имя класса на пространство имен и собственное имя класса
	 * */
	public static function splitClassName($className) {
		// Справится даже если класс не загружен
		preg_match_all('/(.*)[.\\\]([^\\'.'\.]+)$/', $className, $matches);
		return [$matches[1][0], $matches[2][0]];
	}

	/**
	 * Определение имени класса и параметров для его создания из базовой конфигурации
	 * Варианты конфигурации:
	 * 1. 'className' строка, определяющая имя класса
	 * 2. [ 'class' => 'className', 'params' => [...] ] массив, определяющий имя класса и параметры
	 * 3. [...] массив, определяющий параметры
	 * @param $config конфигурация, определяющая класс
	 * @param $defaultClass класс по умолчанию
	 * @param $defaultParams ассоциативный массив параметров по умолчанию
	 * */
	public static function prepareConfig($config, $defaultClass = null, $defaultParams = []) {
		$class = $defaultClass;
		$params = $defaultParams;

		if (is_string($config)) {
			$class = $config;
		} elseif (is_array($config)) {
			if (isset($config['class'])) {
				$class = $config['class'];
				unset($config['class']);
			}
			if (isset($config['params'])) {
				$params = $config['params'];
			} else {
				$params = $config;
			}
		}

		return [
			'class' => $class,
			'params' => $params,
		];
	}

	/**
	 * @var $className - имя класса
	 * @return string|null - имя пакета, в котором класс объявен,
	 *                       либо null, если класс не находится в пакете
	 * */
	public static function defineService($className) {
		$reflected = new \ReflectionClass($className);
		if (!$reflected->inNamespace()) {
			return null;
		}

		$classMap = Autoloader::getInstance()->map;

		$namespace = $reflected->getNamespaceName() . '\\';
		if (array_key_exists($namespace, $classMap->namespaces)) {
			return $classMap->namespaces[$namespace]['package'];
		}

		if (array_key_exists($className, $classMap->classes)) {
			return $classMap->classes[$className]['package'];
		}

		return null;
	}

	/**
	 * Вызов защищенного или приватного метода
	 * */
	public static function call($object, $methodName, $args = []) {
		$rc = new \ReflectionClass($object);
		$method = $rc->getMethod($methodName);

		$isPrivate = $method->isPrivate() || $method->isProtected();
		if ($isPrivate) {
			$method->setAccessible(true);
		}
		if (is_object($object)) {
			$result = $method->invokeArgs($object, $args);
		} else {
			$result = $method->invokeArgs(null, $args);
		}
		if ($isPrivate) {
			$method->setAccessible(false);
		}

		return $result;
	}
}
