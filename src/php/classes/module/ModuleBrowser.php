<?php

namespace lx;

class ModuleBrowser {
	private $service;

	public function __construct($service) {
		$this->service = $service;
	}

	/**
	 *
	 * */
	public static function checkDirectoryIsModule($path) {
		$fullPath = \lx::$conductor->getFullPath($path);

		$directory = new ModuleDirectory($fullPath);
		if (!$directory->exists()) {
			return false;
		}

		$configFile = $directory->getConfigFile();
		$config = $configFile ? $configFile->get() : [];
		$config += \lx::getDefaultModuleConfig();

		return (
			file_exists($directory->getPath() . '/' . $config['frontend'] . '/' . $config['jsMain'])
			||
			file_exists($directory->getPath() . '/' . $config['view'] . '/' . $config['viewIndex'])
		);
	}

	/**
	 *
	 * */
	public function getModulesList() {
		$service = $this->service;
		$dynamicModules = $service->getConfig('service.dynamicModules');
		$modules = (array)$service->getConfig('service.modules');

		if (!$dynamicModules) {
			$dynamicModules = [];
		}

		$result = [
			'dynamic' => [],
			'static' => [],
		];

		foreach ($dynamicModules as $name => $data) {
			$result['dynamic'][] = $name;
		}

		foreach ($modules as $dirName) {
			$dir = $service->dir->get($dirName);
			if (!$dir) {
				continue;
			}

			$dirs = $dir->getDirs()->getData();
			foreach ($dirs as $subdir) {
				if (self::checkDirectoryIsModule($subdir->getPath())) {
					$result['static'][$subdir->getName()] = explode($service->dir->getPath() . '/', $subdir->getPath())[1];
				}
			}
		}

		return $result;
	}
}
