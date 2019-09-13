<?php

namespace lx;

class I18nPluginMap extends I18nMap {
	protected $plugin;
	protected $fullMap;

	public function getFullMap() {
		if (!$this->fullMap) {
			$selfMap = $this->getMap();

			$protoService = $this->plugin->getPrototypeService();
			if ($protoService) {
				$serviceMap = $protoService->i18nMap->getFullMap();
				$selfMap = $this->mapMerge($selfMap, $serviceMap);
			}

			$serviceMap = $this->plugin->getService()->i18nMap->getFullMap();
			$selfMap = $this->mapMerge($selfMap, $serviceMap);

			$this->fullMap = $selfMap;
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
		$fileName = $this->plugin->getConfig('i18nFile');
		if (!$fileName) {
			$fileName = self::DEFAULT_FILE_NAME;
		}

		$fullPath = $this->plugin->conductor->getFullPath($fileName);
		$file = new ConfigFile($fullPath);
		$this->map = $file->exists() ? $file->get() : [];

		foreach ($this->files() as $fileName) {
			$fullPath = $this->plugin->conductor->getFullPath($fileName);
			$file = new ConfigFile($fullPath);
			if ($file->exists()) {
				$this->map = $this->mapMerge($this->map, $file->get());
			}
		}
	}
}
