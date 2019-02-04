<?php

namespace lx;

/**
 * Карта для поиска модулей и классов
 * */
class AutoloadMap {
	private $_packages = [];
	private $_files = [];
	private $_namespaces = [];
	private $_classes = [];

	/* Синглтон */
	private static $instance = null;
	private function __construct() {
		$this->load();
	}
	private function __clone() {}
	public static function getInstance() {
		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Поля, доступные только для чтения
	 * */
	public function __get($name) {
		switch ($name) {
			case 'packages'   : return $this->_packages;
			case 'files'     : return $this->_files;
			case 'namespaces': return $this->_namespaces;
			case 'classes'   : return $this->_classes;
			default: return null;
		}
	}

	/**
	 * Загрузка карт, при необходимости - актуализация кэша
	 * */
	private function load() {
		if (!file_exists(\lx::$conductor->autoloadMapCache) || filemtime(\lx::$conductor->autoloadMap) > filemtime(\lx::$conductor->autoloadMapCache)) {
			if ($this->loadForce()) {
				$this->makeCache();
			}
		} else {
			$this->loadCache();
		}
	}

	/**
	 * Согласно своим картам формирует файл 'autoloadCache.json'
	 * Этот файл отличается уже собранным блоком classes - классы, именование которых не соответствует PSR-0 и PSR-4 (из 'classmap')
	 * */
	private function makeCache() {
		$data = json_encode([
			'packages' => $this->_packages,
			'files' => $this->_files,
			'namespaces' => $this->_namespaces,
			'classes' => $this->_classes,
		]);
		$file = new File(\lx::$conductor->autoloadMapCache);
		$file->put($data);
	}

	/**
	 * Строит карты из 'autoloadCache.json'
	 * */
	private function loadCache() {
		$file = new File(\lx::$conductor->autoloadMapCache);
		$data = $file->get();
		$data = json_decode($data, true);

		$this->_packages = $data['packages'];
		$this->_files = $data['files'];
		$this->_namespaces = $data['namespaces'];
		$this->_classes = $data['classes'];
	}

	/**
	 * Строит карты непосредственно из 'autoload.json'
	 * */
	private function loadForce() {
		$file = new File(\lx::$conductor->autoloadMap);
		if (!$file->exists()) {
			return false;
		}

		$data = $file->get();
		$data = json_decode($data, true);

		$this->parseClasses($data);

		if (isset($data['packages'])) {
			$this->_packages = $data['packages'];
		}
		if (isset($data['files'])) {
			$this->_files = $data['files'];
		}
		if (isset($data['namespaces'])) {
			$this->_namespaces = $data['namespaces'];
		}
		return true;
	}

	/**
	 * Парсим 'classes' и 'directories'
	 * */
	private function parseClasses($data) {
		$sitePath = \lx::sitePath() . '/';

		$classes = [];
		if (isset($data['classes'])) {
			$classes = $data['classes'];
		}
		if (isset($data['directories'])) {
			$dirs = $data['directories'];
			foreach ($dirs as $dir) {
				$d = new Directory($sitePath . $dir['path']);
				if (!$d->exists()) {
					continue;
				}
				$ff = $d->getAllFiles('*.php', Directory::FIND_NAME);
				$ff = $ff->getData();

				foreach ($ff as $f) {
					$classes[] = [
						'package' => $dir['package'],
						'path' => $dir['path'] . '/' . $f,
					];
				}
			}
		}
		foreach ($classes as $item) {
			$classPath = $item['path'];
			$packageName = $item['package'];

			$fullClassPath = $sitePath . $classPath;
			$code = file_get_contents($fullClassPath);
			preg_match_all('/class\s+(.+?)(\s+extends|\s+implements|\s*{)/', $code, $classNames);
			preg_match_all('/namespace\s+(.+?)\s*;/', $code, $namespaces);
			if (empty($namespaces[1])) {
				foreach ($classNames[1] as $className) {
					$this->_classes[$className] = $classPath;
				}
			} else {
				$namespace = $namespaces[1][0];
				foreach ($classNames[1] as $className) {
					$this->_classes[$namespace . '\\' . $className] = [
						'package' => $packageName,
						'path' => $classPath,
					];
				}
			}
		}
	}
}
