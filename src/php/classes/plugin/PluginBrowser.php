<?php

namespace lx;

class PluginBrowser {
	private $plugin;

	public function __construct($plugin) {
		$this->plugin = $plugin;
	}

	/**
	 *
	 * */
	public static function checkDirectoryIsPlugin($path) {
		$fullPath = \lx::$app->conductor->getFullPath($path);

		$directory = new PluginDirectory($fullPath);
		if (!$directory->exists()) {
			return false;
		}

		$configFile = $directory->getConfigFile();
		$config = $configFile ? $configFile->get() : [];
		$config += \lx::$app->getDefaultPluginConfig();

		if (isset($config['frontend']) && isset($config['jsMain'])
			&& file_exists($directory->getPath() . '/' . $config['frontend'] . '/' . $config['jsMain'])
		) return true;

		if (isset($config['rootSnippet'])
			&& file_exists($directory->getPath() . '/' . $config['rootSnippet'])
		) return true;

		return false;
	}

	/**
	 *
	 * */
	public function getPluginsMap($service) {
		$dynamicPlugins = $service->getConfig('service.dynamicPlugins');
		$plugins = (array)$service->getConfig('service.plugins');

		if (!$dynamicPlugins) {
			$dynamicPlugins = [];
		}

		$result = [
			'dynamic' => [],
			'static' => [],
		];

		foreach ($dynamicPlugins as $name => $data) {
			$result['dynamic'][] = $name;
		}

		foreach ($plugins as $dirName) {
			$dir = $service->directory->get($dirName);
			if (!$dir) {
				continue;
			}

			$dirs = $dir->getDirs()->getData();
			foreach ($dirs as $subdir) {
				if (self::checkDirectoryIsPlugin($subdir->getPath())) {
					$result['static'][$subdir->getName()] = explode($service->getPath() . '/', $subdir->getPath())[1];
				}
			}
		}

		return $result;
	}
}
