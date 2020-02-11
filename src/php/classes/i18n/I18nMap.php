<?php

namespace lx;

class I18nMap extends Object {
	use ApplicationToolTrait;

	const DEFAULT_FILE_NAME = 'i18n';

	protected $map = null;
	protected $tags;

	public function __construct($config = []) {
		parent::__construct($config);

		foreach ($config as $key => $value) {
			if (property_exists($this, $key)) {
				$this->$key = $value;
			}
		}

		$this->init($config);
	}

	protected function init($config) {
		// pass
	}

	public function getMap() {
		if ($this->map === null) {
			$this->loadMap();
		}
		
		return $this->map;
	}

	public function getFullMap() {
		return $this->getMap();
	}

	public function add($map, $rewrite = false) {
		if (!is_array($map) && is_object($map) && method_exists($map, 'toArray')) {
			$map = $map->toArray();
		}
		
		if (!is_array($map)) {
			return;
		}
		
		$this->loadMap();
		$this->map = $this->mapMerge($this->map, $map, $rewrite);
	}

	/**
	 * Метод для переопределения, чтобы подключать файлы не через конфигурацию, а через код
	 * @return array
	 */
	protected function files() {
		return [];
	}

	protected function loadMap() {
		if ($this->map !== null) {
			return;
		}

		$this->map = [];
	}

	protected function mapMerge($map1, $map2, $rewrite = false) {
		$codes = $this->app->language->codes;

		foreach ($map2 as $key => $value) {
			if (!in_array($key, $codes)) {
				if (is_array($this->tags) && array_key_exists($key, $this->tags)) {
					$key = $this->tags[$key];
				}
			}

			if (!in_array($key, $codes)) {
				continue;
			}

			if (!array_key_exists($key, $map1)) {
				$map1[$key] = $value;
			} else {
				if ($rewrite) {
					$map1[$key] = $value + $map1[$key];
				} else {
					$map1[$key] += $value;
				}
			}
		}

		return $map1;
	}


	// // Остаток от старой логики. Может сгодится для TODO про ::files()
	// protected function loadFromMapFiles() {
	// 	$result = [];

	// 	$fileNames = $this->mapFiles();
	// 	foreach ($fileNames as $fileName) {
	// 		$path = $this->app->conductor->getFullPath($fileName);
	// 		$file = new ConfigFile($path);
	// 		if ($file->exists()) {
	// 			$result += $file->get();
	// 		}
	// 	}

	// 	return $result;
	// }
}
