<?php

namespace lx;

/**
 * Класс для обозревания пакетов со стороны
 * */
class PackageBrowser {
	/**
	 *
	 * */
	public static function getPackagesList() {
		return Autoloader::getInstance()->map->packages;
	}

	/**
	 *
	 * */
	public static function getServicesList() {
		$list = self::getPackagesList();
		$result = [];

		foreach ($list as $name => $path) {
			if (self::checkPackageIsService($name)) {
				$result[$name] = $path;
			}
		}

		return $result;
	}

	/**
	 *
	 * */
	public static function checkDirectoryIsPackage($packagePath) {
		$fullPath = \lx::$app->conductor->getFullPath($packagePath);

		$directory = new PackageDirectory($fullPath);
		if (!$directory->exists()) {
			return false;
		}
		$configFile = $directory->getConfigFile();

		return $configFile !== null;
	}

	/**
	 *
	 * */
	public static function checkDirectoryIsService($packagePath) {
		$fullPath = \lx::$app->conductor->getFullPath($packagePath);

		$directory = new PackageDirectory($fullPath);
		if (!$directory->exists()) {
			return false;
		}
		$configFile = $directory->getConfigFile();
		$config = $configFile->get();

		return array_key_exists('service', $config);
	}

	/**
	 *
	 * */
	public static function checkPackageIsService($packageName) {
		$list = self::getPackagesList();
		if (!array_key_exists($packageName, $list)) {
			return false;
		}

		return self::checkDirectoryIsService($list[$packageName]);
	}
	
}
