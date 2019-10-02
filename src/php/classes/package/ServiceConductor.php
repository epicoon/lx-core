<?php

namespace lx;

class ServiceConductor {
	private $service = null;

	/**
	 *
	 * */
	public function __construct($service) {
		$this->service = $service;
	}

	public function getRootPath() {
		return $this->getPath();
	}

	/**
	 * @return string - путь к сервису на сервере
	 * */
	public function getPath() {
		return $this->service->directory->getPath();
	}

	public function getSystemPath() {
		return $this->getPath() . '/.system';
	}

	/**
	 * @param $fileName string - путь относительно корня сервиса
	 * @return string - полный путь к файлу
	 * */
	public function getFullPath($fileName) {
		if ($fileName{0} == '@') {
			$fileName = $this->decodeAlias($fileName);
		}

		return $this->service->app->conductor->getFullPath($fileName, $this->getPath());
	}

	/**
	 * @param $pluginName string - имя плагина, путь к которому нужно найти
	 * @return string - полный путь к плагину
	 * */
	public function getPluginPath($pluginName) {
		$pluginDirs = (array)$this->service->getConfig('service.plugins');
		foreach ($pluginDirs as $dir) {
			if ($dir != '') $dir .= '/';
			$fullPath = $this->getPath() . '/' . $dir . $pluginName;

			if (file_exists($fullPath)) {
				return $fullPath;
			}
		}
		return null;
	}

	/**
	 *
	 * */
	public function getDefaultModelPath() {
		$models = $this->service->getConfig('service.models');
		if ($models === null) return false;

		$models = (array)$models;
		return $this->getFullPath($models[0]);
	}

	/**
	 * Ищет путь к файлу модели. Файл должен называться как модель, расширение только .yaml
	 * */
	public function getModelPath($name) {
		// Сначала проверяется карта [имя_модели => путь_к_файлу_с_моделью]
		$modelsMap = $this->service->getConfig('service.modelsMap');
		if ($modelsMap !== null) {
			if (array_key_exists($name, $modelsMap)) {
				$fullPath = $this->getFullPath($modelsMap[$name]);
				if (file_exists($fullPath)) {
					return $fullPath;
				}
			}
		}

		// Если не нашли - проверяется массив с директориями, где лежат модели
		$models = $this->service->getConfig('service.models');
		if ($models === null) return false;

		$models = (array)$models;

		foreach ($models as $dir) {
			$path = $this->getFullPath($dir);
			$d = new Directory($path);
			$f = $d->find($name . '.yaml', Directory::FIND_NAME);
			if ($f) return $f;
		}

		return false;
	}

	/**
	 * Ищет пути к файлам моделей
	 * */
	public function getModelsPath() {
		$result = [];

		$modelsMap = $this->service->getConfig('service.modelsMap');
		if ($modelsMap !== null) {
			$result = $modelsMap;
		}

		$models = $this->service->getConfig('service.models');
		if ($models === null) return $result;

		$models = (array)$models;

		foreach ($models as $dir) {
			$path = $this->getFullPath($dir);
			if (!file_exists($path)) {
				continue;
			}
			$d = new Directory($path);

			$ff = $d->getContent([
				'findType' => Directory::FIND_NAME,
				'mask' => '*.yaml',
				'ext' => false
			]);
			$ff->each(function($a) use ($path, &$result) {
				$result[$a] = "$path/$a.yaml";
			});
		}

		return $result;
	}

	public function getMigrationDirectory() {
		$dir = new Directory($this->getSystemPath() . '/migrations');
		$dir->make();
		return $dir;
	}

	public function getModuleMapDirectory() {
		$dir = new Directory($this->getSystemPath() . '/modules');
		$dir->make();
		return $dir;
	}

	private function decodeAlias($path) {
		$aliases = $this->service->getConfig('service.aliases');
		if (!$aliases) return $path;

		$result = $path;
		while (true) {
			preg_match_all('/^@([_\w][_\-\w\d]*?)(\/|$)/', $result, $arr);
			if (empty($arr) || empty($arr[0])) return $result;

			$mask = $arr[0][0];
			$alias = $arr[1][0];
			if (!array_key_exists($alias, $aliases)) return $result;

			$alias = $aliases[$alias] . $arr[2][0];
			$result = str_replace($mask, $alias, $result);
		}
	}
}
