<?php

namespace lx;

class PluginBrowser
{
	public static function checkDirectoryIsPlugin(string $path): bool
	{
		$fullPath = \lx::$app->conductor->getFullPath($path);

		$directory = new PluginDirectory($fullPath);
		if (!$directory->exists()) {
			return false;
		}

		$configFile = $directory->getConfigFile();
		$config = $configFile ? $configFile->get() : [];
		$config += \lx::$app->getDefaultPluginConfig();

		if (isset($config['jsMain'])
			&& file_exists($directory->getPath() . '/' . $config['jsMain'])
		) return true;

		if (isset($config['rootSnippet'])
			&& file_exists($directory->getPath() . '/' . $config['rootSnippet'])
		) return true;

		return false;
	}

	public static function getPluginsDataMap(Service $service): array
	{
		return [
			'dynamic' => $service->getDynamicPluginsDataList(),
			'static' => $service->getStaticPluginsDataList(),
		];
	}
}
