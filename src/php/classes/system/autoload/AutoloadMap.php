<?php

namespace lx;

/**
 * Карта для поиска модулей и классов
 * */
class AutoloadMap {
	private $sitePath;
	private $autoloadMapPath;
	private $autoloadMapCachePath;
	
	private $_packages = [];
	private $_files = [];
	private $_namespaces = [];
	private $_classes = [];

	public function __construct($sitePath) {
		$this->sitePath = $sitePath;
		$systemPath = \lx::$conductor->getSystemPath('systemPath');
		$this->autoloadMapPath = $systemPath . '/autoload.json';
		$this->autoloadMapCachePath = $systemPath . '/autoloadCache.json';
		
		$this->load();
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
	 *
	 * */
	public function reset() {
		if ($this->loadForce()) {
			$this->makeCache();
		}
	}

	/**
	 * Загрузка карт, при необходимости - актуализация кэша
	 * */
	private function load() {
		if (!file_exists($this->autoloadMapCachePath)
			|| filemtime($this->autoloadMapPath) > filemtime($this->autoloadMapCachePath)
		) {
			$this->reset();
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
		$file = new File($this->autoloadMapCachePath);
		$file->put($data);
	}

	/**
	 * Строит карты из 'autoloadCache.json'
	 * */
	private function loadCache() {
		$file = new File($this->autoloadMapCachePath);
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
		$file = new File($this->autoloadMapPath);
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
		$sitePath = $this->sitePath . '/';

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
