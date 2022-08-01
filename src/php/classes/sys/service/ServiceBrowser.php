<?php

namespace lx;

class ServiceBrowser
{
	public static function getServicePathesList(): array
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
	 * @return array<Service>
	 */
	public static function getServicesList(): array
	{
		$list = self::getServicePathesList();
		foreach ($list as $name => &$value) {
			$value = \lx::$app->getService($name);
		}
		unset($value);

		return $list;
	}

	public static function checkDirectoryIsPackage(string $packagePath): bool
	{
		$fullPath = \lx::$app->conductor->getFullPath($packagePath);

		$directory = new ServiceDirectory($fullPath);
		if (!$directory->exists()) {
			return false;
		}
		$configFile = $directory->getConfigFile();

		return $configFile !== null;
	}

	public static function checkDirectoryIsService(string $packagePath): bool
	{
		$fullPath = \lx::$app->conductor->getFullPath($packagePath);

		$directory = new ServiceDirectory($fullPath);
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

	public static function checkPackageIsService(string $packageName): bool
	{
		$list = self::getPackagePathesList();
		if (!array_key_exists($packageName, $list)) {
			return false;
		}

		return self::checkDirectoryIsService($list[$packageName]);
	}

    private static function getPackagePathesList(): array
    {
        return Autoloader::getInstance()->map->services;
    }
}
