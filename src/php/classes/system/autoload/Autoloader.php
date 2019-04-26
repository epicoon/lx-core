<?php

namespace lx;

class Autoloader {
	/** @var lx\AutoloadMap */
	private $_map = null;

	private $_systemClassMap = null;
	private $_path = null;

	/* Синглтон */
	private static $instance = null;
	protected function __construct() {}
	protected function __clone() {}
	public static function getInstance() {
		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __get($name) {
		if ($name == 'map') {
			if ($this->_map === null) {
				$this->_map = AutoloadMap::getInstance();
			}
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
	 * Инициализация метода-загрузчика классов
	 * */
	public function init() {
		spl_autoload_register([$this, 'load']);
	}

	/**
	 * Формирует список с информацией по используемым пакетам
	 * */
	public function getPackagesList() {
		$sitePath = \lx::sitePath() . '/';
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
			throw new \Exception("Autoload failed. Class '$className' not found", 400);
		}

		require_once($path);

		// При подключении виджета сообщаем js-компилятору и проверяем карту локализации
		if (ClassHelper::checkInstance($className, Rect::class)) {
			JsCompiler::noteUsedWidget($className);
			$i18n = dirname($path) . '/i18n.yaml';
			if (file_exists($i18n)) {
				\lx::useI18n($i18n);
			}
		}
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
		$map = $this->classMap();
		if (array_key_exists($name, $map)) {
			// Стандартная логика автозагрузки - имя файла соответствует имени класса
			$path = $this->path() . '/' . $map[$name] . '/' . $name . '.php';
			if (file_exists($path)) {
				return $path;
			}
		}

		// Проверка на встроенный в платформу виджет
		$path = \lx::$conductor->getSystemPath('lxWidgets') . '/' . $name . '/_main.php' ;
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
		$path = \lx::sitePath() . '/' . str_replace('\\', '/', $className) . '.php';
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
			$basePath = \lx::sitePath() . '/' . $this->map->packages[ $data['package'] ] . '/';
			foreach ($data['pathes'] as $innerPath) {
				$path = $basePath . $innerPath;
				if (!preg_match('/\/$/', $path)) $path .= '/';

				// Стандартная логика автозагрузки - имя файла соответствует имени класса
				$fullPath = $path . $relativePath . '.php';
				if (file_exists($fullPath)) {
					return $fullPath;
				}

				// Расширение логики автолоэйда до возможности использовать директории для классов
				$fullPath = $path . $relativePath . '/'
					. \lx::$conductor->getSystemPath('classAutoloadMainFile') . '.php';
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
			$path = \lx::sitePath() . '/' . $classes[$className]['path'];
			if (file_exists($path)) {
				return $path;
			}
		}
		return false;
	}

	/**
	 * Подгружает и кэширует карту классов
	 * */
	private function classMap() {
		if ($this->_systemClassMap === null) {
			$this->_systemClassMap = require($this->path() . '/classMap.php');
		}
		return $this->_systemClassMap;
	}

	/**
	 * Автозагрузчику нужно знать о пути к папке 'php' самому, т.к. он не может полагаться на кондуктор (его еще надо загрузить)
	 * */
	private function path() {
		if ($this->_path === null) {
			$this->_path = dirname(__DIR__, 3);
		}
		return $this->_path;
	}
}
