<?php

namespace lx;

/**
 * Class PluginBrowser
 * @package lx
 */
class PluginBrowser
{
	/** @var Plugin */
	private $plugin;

	/**
	 * PluginBrowser constructor.
	 * @param Plugin $plugin
	 */
	public function __construct($plugin)
	{
		$this->plugin = $plugin;
	}

	/**
	 * @param string $path
	 * @return bool
	 */
	public static function checkDirectoryIsPlugin($path)
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

	/**
	 * @param Service $service
	 * @return array
	 */
	public static function getPluginsDataMap($service)
	{
		return [
			'dynamic' => $service->getDynamicPluginsDataList(),
			'static' => $service->getStaticPluginsDataList(),
		];
	}
}
