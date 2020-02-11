<?php

namespace lx;

class I18nPluginMap extends I18nMap implements FusionComponentInterface {
	use FusionComponentTrait;

	protected $fullMap;

	public function getPlugin() {
		return $this->owner;
	}

	public function getFullMap() {
		if (!$this->fullMap) {
			$selfMap = $this->getMap();

			$protoService = $this->getPlugin()->getPrototypeService();
			if ($protoService) {
				$serviceMap = $protoService->i18nMap->getFullMap();
				$selfMap = $this->mapMerge($selfMap, $serviceMap);
			}

			$serviceMap = $this->getPlugin()->getService()->i18nMap->getFullMap();
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
		$fileName = $this->getPlugin()->getConfig('i18nFile');
		if (!$fileName) {
			$fileName = self::DEFAULT_FILE_NAME;
		}

		$fullPath = $this->getPlugin()->conductor->getFullPath($fileName);
		$file = new ConfigFile($fullPath);
		$this->map = $file->exists() ? $file->get() : [];

		foreach ($this->files() as $fileName) {
			$fullPath = $this->getPlugin()->conductor->getFullPath($fileName);
			$file = new ConfigFile($fullPath);
			if ($file->exists()) {
				$this->map = $this->mapMerge($this->map, $file->get());
			}
		}
	}
}
