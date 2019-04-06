<?php

namespace lx;

class I18nMap extends DataObject {
	const DEFAULT_FILE_NAME = 'i18n';

	private static $appMap = null;
	private $module = null;
	private $service = null;
	private $map = null;

	public function __construct($config = []) {
		if (isset($config['service'])) {
			$this->service = $config['service'];
			unset($config['service']);
		}

		if (isset($config['module'])) {
			$this->module = $config['module'];
			$this->service = $this->module->getService();
			unset($config['module']);
		}

		$this->setProperties($config);
	}

	/**
	 *
	 * */
	public function getSelfMap() {
		if ($this->map === null) {
			$this->loadMap();
		}

		return $this->map;
	}

	/**
	 *
	 * */
	public function getFullMap() {
		if (self::$appMap === null) {
			self::loadAppMap();
		}

		return $this->getSelfMap() + self::$appMap;
	}

	/**
	 * @param $map \lx\I18nMap
	 * */
	public function add($map) {
		$this->map += $map->toArray();
	}

	/**
	 * Метод для переопределения чтобы подключать кастомные файлы с картами перевода
	 * */
	public function mapFiles() {
		return [];
	}

	/**
	 * @param $obj \lx\Service | \lx\Module | null
	 * */
	protected static function getFile($obj = null) {
		if ($obj === null) {
			$path = \lx::sitePath();
			$fileName = \lx::getConfig('i18nFile');
		} elseif ($obj instanceof Module) {
			$path = $obj->getPath();
			$fileName = $obj->getConfig('i18nFile');
		} elseif ($obj instanceof Service) {
			$path = $obj->getPath();
			$fileName = $obj->getConfig('service.i18nFile');
		} else {
			return null;
		}

		if (!$fileName) {
			$fileName = self::DEFAULT_FILE_NAME;
		}

		$fullPath = \lx::$conductor->getFullPath($fileName, $path);
		$file = new ConfigFile($fullPath);
		return $file;
	}

	/**
	 *
	 * */
	private function loadMap() {
		$this->map = [];

		if ($this->module) {
			$this->mapMerge($this->loadFromMapFiles());

			$file = self::getFile($this->module);
			if ($file->exists()) {
				$this->mapMerge($file->get());
			}

			$this->map += $this->service->i18nMap->getSelfMap();
			if ($this->module->prototype) {
				$this->mapMerge($this->module->prototypeService()->i18nMap->getSelfMap());
			}

		} elseif ($this->service) {
			$this->mapMerge($this->loadFromMapFiles());

			$file = self::getFile($this->service);
			if ($file->exists()) {
				$this->mapMerge($file->get());
			}
		}
	}

	/**
	 *
	 * */
	private function loadFromMapFiles() {
		$result = [];

		$fileNames = $this->mapFiles();
		foreach ($fileNames as $fileName) {
			$path = \lx::$conductor->getFullPath($fileName);
			$file = new ConfigFile($path);
			if ($file->exists()) {
				$result += $file->get();
			}
		}

		return $result;
	}

	/**
	 *
	 * */
	private function mapMerge($map) {
		$codes = \lx::$language->codes;

		foreach ($map as $key => $value) {
			if (!in_array($key, $codes)) {
				if (is_array($this->tags) && array_key_exists($key, $this->tags)) {
					$key = $this->tags[$key];
				}
			}

			if (!in_array($key, $codes)) {
				continue;
			}

			if (!array_key_exists($key, $this->map)) {
				$this->map[$key] = $value;
			} else {
				$this->map[$key] += $value;
			}
		}
	}

	/**
	 *
	 * */
	private static function loadAppMap() {
		$file = self::getFile();
		self::$appMap = $file->exists()
			? $file->get()
			: [];
	}
}
