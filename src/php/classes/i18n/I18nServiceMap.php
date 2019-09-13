<?php

namespace lx;

class I18nServiceMap extends I18nMap {
	protected $service;
	protected $fullMap;

	public function getFullMap() {
		if (!$this->fullMap) {
			$selfMap = $this->getMap();
			$appMap = $this->app->i18nMap->getMap();
			$this->fullMap = $this->mapMerge($selfMap, $appMap);
		}

		return $this->fullMap;
	}

	protected function loadMap() {
		if ($this->map !== null) {
			return;
		}

		/*
		TODO
		сделать возможность указывать в конфиге несколько файлов и даже каталогов с картами
		*/
		$fileName = $this->service->getConfig('service.i18nFile');
		if (!$fileName) {
			$fileName = self::DEFAULT_FILE_NAME;
		}

		$fullPath = $this->service->conductor->getFullPath($fileName);
		$file = new ConfigFile($fullPath);
		$this->map = $file->exists() ? $file->get() : [];

		foreach ($this->files() as $fileName) {
			$fullPath = $this->service->conductor->getFullPath($fileName);
			$file = new ConfigFile($fullPath);
			if ($file->exists()) {
				$this->map = $this->mapMerge($this->map, $file->get());
			}
		}
	}
}
