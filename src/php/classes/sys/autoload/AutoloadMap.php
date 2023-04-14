<?php

namespace lx;

/**
 * @property-read array $services
 * @property-read array $files
 * @property-read array $namespaces
 * @property-read array $classes
 */
class AutoloadMap
{
	private string $sitePath;
	private string $autoloadMapPath;
	private string $autoloadMapCachePath;
	private array $_services = [];
	private array $_files = [];
	private array $_namespaces = [];
	private array $_classes = [];

	public function __construct(string $sitePath)
	{
		$this->sitePath = $sitePath;
		$systemPath = \lx::$conductor->getSystemPath();
		$this->autoloadMapPath = $systemPath . '/autoload.json';
		$this->autoloadMapCachePath = $systemPath . '/autoloadCache.json';

		$this->load();
	}

	public function __get(string $name): ?array
	{
		switch ($name) {
			case 'services'   :
				return $this->_services;
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
	public function reset(): void
	{
		if ($this->loadForce()) {
			$this->makeCache();
		}
	}


	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * PRIVATE
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	private function load(): void
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
	private function makeCache(): void
	{
		$data = json_encode([
			'services' => $this->_services,
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
	private function loadCache(): void
	{
		$file = new File($this->autoloadMapCachePath);
		$data = $file->get();
		$data = json_decode($data, true);

		$this->_services = $data['services'];
		$this->_files = $data['files'];
		$this->_namespaces = $data['namespaces'];
		$this->_classes = $data['classes'];
	}

	/**
	 * Load map from 'autoload.json'
	 */
	private function loadForce(): bool
	{
		$file = new File($this->autoloadMapPath);
		if (!$file->exists()) {
			return false;
		}

		$data = $file->get();
		$data = json_decode($data, true);

		$this->parseClasses($data);

		if (isset($data['services'])) {
			$this->_services = $data['services'];
		}
		if (isset($data['files'])) {
			$this->_files = $data['files'];
		}
		if (isset($data['namespaces'])) {
			$this->_namespaces = $data['namespaces'];
		}
		return true;
	}

	private function parseClasses(array $data): void
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
				$ff = $d->getAllFileNames(['mask' => '*.php']);
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
