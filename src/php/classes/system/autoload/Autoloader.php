<?php

namespace lx;

class Autoloader {
	const CLASS_MAIN_FILE = '_main';

	private $_sitePath;
	private $srcPath;
	private $phpPath;

	/** @var lx\AutoloadMap */
	private $_map = null;
	private $_systemClassMap = null;

	private static $instance = null;
	private function __construct() {}
	private function __clone() {}
	public static function getInstance() {
		if (self::$instance === null) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	public function init($sitePath) {
		$this->_sitePath = $sitePath;
		//TODO - возможно есть смысл вынести пути в приложение, передавать сюда приложение
		$this->srcPath = $this->_sitePath . '/vendor/lx/lx-core/src';
		$this->phpPath = $this->srcPath . '/php';
		$this->_systemClassMap = require($this->phpPath . '/classMap.php');
		spl_autoload_register([$this, 'load']);

		$this->_map = new AutoloadMap($this->_sitePath);
	}
	
	public function __get($name) {
		if ($name == 'sitePath') {
			return $this->_sitePath;
		}
		
		if ($name == 'map') {
			return $this->_map;
		}

		return null;
	}

	/**
	 * Попытка найти файл с кодом класса
	 * @var $className - имя класса
	 * @return string|bool - путь к файлу с кодом класса либо false
	 * */
	public function getClassPath($className) {
		$path = $this->getSystemClassPath($className);
		if ($path !== false) return $path;

		$path = $this->getClientClassPath($className);
		if ($path !== false) return $path;

		$path = $this->getClassPathWithNamespaceMap($className);
		if ($path !== false) return $path;

		$path = $this->getClassPathWithClassMap($className);
		if ($path !== false) return $path;

		return false;
	}

	/**
	 * Формирует список с информацией по используемым пакетам
	 * */
	public function getPackagesList() {
		$sitePath = $this->sitePath . '/';
		$list = [];
		foreach ($this->map->packages as $name => $path) {
			$fullPath = $sitePath . $path;
			$pack = new PackageDirectory($fullPath);

			$config = $pack->getConfigFile();
			if ($config === null) {
				//todo возможно тут надо сообщение или исключение - пакет всегда должен иметь конфигурационный файл
				continue;
			}

			$configData = DataObject::create($config->get());
			$description = $configData->getFirstDefined('description', 'NONE');

			$list[] = [
				'name' => $name,
				'description' => $description,
			];
		}
		return $list;
	}

	/**
	 * Метод-загрузчик классов
	 * */
	public function load($className) {
		$path = $this->getClassPath($className);

		if ($path === false) {
			// Autoload failed. Class '$className' not found
			return false;
		}

		require_once($path);
	}

	/**
	 * Попытка найти файл с кодом системного класса
	 * Все системные классы находятся в пространстве имен 'lx' и только в нем
	 * @var $className - имя класса
	 * @return string|bool - путь к файлу с кодом класса либо false
	 * */
	private function getSystemClassPath($className) {
		$arr = explode('\\', $className);
		if (empty($arr) || count($arr) != 2 || $arr[0] != 'lx') {
			return false;
		}

		$name = $arr[1];
		$map = $this->_systemClassMap;
		if (array_key_exists($name, $map)) {
			// Стандартная логика автозагрузки - имя файла соответствует имени класса
			$path = $this->phpPath . '/' . $map[$name] . '/' . $name . '.php';
			if (file_exists($path)) {
				return $path;
			}
		}

		// Проверка на встроенный в платформу виджет
		$path = \lx::$conductor->getSystemPath('lxWidgets') . '/' . $name . '/_main.php' ;
		if (file_exists($path)) {
			return $path;
		}
		$path = \lx::$conductor->getSystemPath('lxWidgets') . '/' . $name . '/' . (explode('\\', $className)[1]) . '.php' ;
		if (file_exists($path)) {
			return $path;
		}

		return false;
	}

	/**
	 * Попытка найти файл с кодом клиентского класса уровня приложения
	 * @var $className - имя класса
	 * @return string|bool - путь к файлу с кодом класса либо false
	 * */
	private function getClientClassPath($className) {
		$path = $this->sitePath . '/' . str_replace('\\', '/', $className) . '.php';
		if (file_exists($path)) {
			return $path;
		}
		return false;
	}

	/**
	 * Попытка найти файл с кодом класса согласно PSR-карте пространств имен
	 * @var $className - имя класса
	 * @return string|bool - путь к файлу с кодом класса либо false
	 * */
	private function getClassPathWithNamespaceMap($className) {
		$namespaces = $this->map->namespaces;
		foreach ($namespaces as $namespace => $data) {
			$reg = '/^' . $namespace . '/';
			$reg = str_replace('\\', '\\\\', $reg);
			if (!preg_match($reg, $className)) {
				continue;
			}

			$subName = explode($namespace, $className)[1];
			$relativePath = str_replace('\\', '/', $subName);
			$basePath = $this->sitePath . '/' . $this->map->packages[ $data['package'] ] . '/';
			foreach ($data['pathes'] as $innerPath) {
				$path = $basePath . $innerPath;
				if (!preg_match('/\/$/', $path)) $path .= '/';

				// Стандартная логика автозагрузки - имя файла соответствует имени класса
				$fullPath = $path . $relativePath . '.php';
				if (file_exists($fullPath)) {
					return $fullPath;
				}

				// Расширение логики автолоэйда до возможности использовать директории для классов
				$fullPath = $path . $relativePath . '/' . self::CLASS_MAIN_FILE . '.php';
				if (file_exists($fullPath)) {
					return $fullPath;
				}

				preg_match_all('/[^\\'.'\]+$/', $className, $matches);
				$propClassName = empty($matches[0]) ? '' : $matches[0][0];

				$fullPath = $path . $relativePath . '/_' . $propClassName . '.php';
				if (file_exists($fullPath)) {
					return $fullPath;
				}

				$fullPath = $path . $relativePath . '/' . $propClassName . '.php';
				if (file_exists($fullPath)) {
					return $fullPath;
				}
			}
		}

		return false;
	}

	/**
	 * Попытка найти файл с кодом класса, который объявлен не по PSR
	 * @var $className - имя класса
	 * @return string|bool - путь к файлу с кодом класса либо false
	 * */
	private function getClassPathWithClassMap($className) {
		$classes = $this->map->classes;
		if (array_key_exists($className, $classes)) {
			$path = $this->sitePath . '/' . $classes[$className]['path'];
			if (file_exists($path)) {
				return $path;
			}
		}
		return false;
	}
}
