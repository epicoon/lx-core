<?php


namespace lx;


class I18nApplicationMap extends I18nMap {
	private $used = [];

	public function inUse($name) {
		return array_search($name, $this->used) !== false;
	}

	public function noteUse($name) {
		$this->used[] = $name;
	}

	protected function loadMap() {
		if ($this->map !== null) {
			return $this->map;
		}

		/*
		TODO
		сделать возможность указывать в конфиге несколько файлов и даже каталогов с картами
		*/
		$fileName = $this->app->getConfig('i18nFile');
		if (!$fileName) {
			$fileName = self::DEFAULT_FILE_NAME;
		}

		$fullPath = $this->app->conductor->getFullPath($fileName);
		$file = new ConfigFile($fullPath);
		$this->map = $file->exists() ? $file->get() : [];
	}
}
