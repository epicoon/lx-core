<?php

namespace lx;

/**
 * Class PackageBrowser
 * @package lx
 */
class PackageBrowser
{
	/**
	 * @return array
	 */
	public static function getPackagePathesList()
	{
		return Autoloader::getInstance()->map->packages;
	}

	/**
	 * @return array
	 */
	public static function getServicePathesList()
	{
		$list = self::getPackagePathesList();
		$result = [];

		foreach ($list as $name => $path) {
			if (self::checkPackageIsService($name)) {
				$result[$name] = $path;
			}
		}

		return $result;
	}

	/**
	 * @return Service[]
	 */
	public static function getServicesList()
	{
		$list = self::getServicePathesList();
		foreach ($list as $name => &$value) {
			$value = \lx::$app->getService($name);
		}
		unset($value);

		return $list;
	}

	/**
	 * @param string $packagePath
	 * @return bool
	 */
	public static function checkDirectoryIsPackage($packagePath)
	{
		$fullPath = \lx::$app->conductor->getFullPath($packagePath);

		$directory = new PackageDirectory($fullPath);
		if (!$directory->exists()) {
			return false;
		}
		$configFile = $directory->getConfigFile();

		return $configFile !== null;
	}

	/**
	 * @param string $packagePath
	 * @return bool
	 */
	public static function checkDirectoryIsService($packagePath)
	{
		$fullPath = \lx::$app->conductor->getFullPath($packagePath);

		$directory = new PackageDirectory($fullPath);
		if (!$directory->exists()) {
			return false;
		}

		$configFile = $directory->getConfigFile();
		if (!$configFile) {
		    return false;
        }

		$config = $configFile->get();
		return array_key_exists('service', $config);
	}

	/**
	 * @param string $packageName
	 * @return bool
	 */
	public static function checkPackageIsService($packageName)
	{
		$list = self::getPackagePathesList();
		if (!array_key_exists($packageName, $list)) {
			return false;
		}

		return self::checkDirectoryIsService($list[$packageName]);
	}

}
