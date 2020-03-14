<?php

namespace lx;

/**
 * Class AutoloadMap
 * @package lx
 *
 * @property-read array $packages
 * @property-read array $files
 * @property-read array $namespaces
 * @property-read array $classes
 */
class AutoloadMap
{
	/** @var string */
	private $sitePath;

	/** @var string */
	private $autoloadMapPath;

	/** @var string */
	private $autoloadMapCachePath;

	/** @var array */
	private $_packages = [];

	/** @var array */
	private $_files = [];

	/** @var array */
	private $_namespaces = [];

	/** @var array */
	private $_classes = [];

	/**
	 * AutoloadMap constructor.
	 * @param string $sitePath
	 */
	public function __construct($sitePath)
	{
		$this->sitePath = $sitePath;
		$systemPath = \lx::$conductor->getSystemPath();
		$this->autoloadMapPath = $systemPath . '/autoload.json';
		$this->autoloadMapCachePath = $systemPath . '/autoloadCache.json';

		$this->load();
	}

	/**
	 * @param string $name
	 * @return array|null
	 */
	public function __get($name)
	{
		switch ($name) {
			case 'packages'   :
				return $this->_packages;
			case 'files'     :
				return $this->_files;
			case 'namespaces':
				return $this->_namespaces;
			case 'classes'   :
				return $this->_classes;
			default:
				return null;
		}
	}

	/**
	 * Renew map and rebuild cache
	 */
	public function reset()
	{
		if ($this->loadForce()) {
			$this->makeCache();
		}
	}


	/*******************************************************************************************************************
	 * PRIVATE
	 ******************************************************************************************************************/

	private function load()
	{
		if (!file_exists($this->autoloadMapCachePath)
			|| filemtime($this->autoloadMapPath) > filemtime($this->autoloadMapCachePath)
		) {
			$this->reset();
		} else {
			$this->loadCache();
		}
	}

	/**
	 * Makes cache file 'autoloadCache.json'
	 * Cache file as opposite to 'autoload.json' has already built block 'classes'
	 * according to 'classmap' autoload configurations
	 */
	private function makeCache()
	{
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
	 * Load map from 'autoloadCache.json'
	 */
	private function loadCache()
	{
		$file = new File($this->autoloadMapCachePath);
		$data = $file->get();
		$data = json_decode($data, true);

		$this->_packages = $data['packages'];
		$this->_files = $data['files'];
		$this->_namespaces = $data['namespaces'];
		$this->_classes = $data['classes'];
	}

	/**
	 * Load map from 'autoload.json'
	 */
	private function loadForce()
	{
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
	 * @param array $data
	 */
	private function parseClasses($data)
	{
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
				$ff = $ff->toArray();

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
