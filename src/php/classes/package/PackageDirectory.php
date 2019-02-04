<?php

namespace lx;

class PackageDirectory extends Directory {
	/**
	 * Любой пакет должен иметь конфигурационный файл
	 * Если конфигурационно файла нет - данная директория не пакет
	 * */
	public function getConfigFile() {
		$configPathes = \lx::$conductor->packageConfig;
		$path = $this->getPath();
		foreach ($configPathes as $configPath) {
			$fullPath = $path . '/' . $configPath;
			if (file_exists($fullPath)) {
				return new ConfigFile($fullPath);
			}
		}
		return null;
	}

	/**
	 * Является ли пакет lx-пакетом (конфигурационный файл должен быть именно от lx)
	 * @return bool
	 * */
	public function isLx() {
		$lxConfigPathes = \lx::$conductor->packageLxConfig;
		$path = $this->getPath();
		foreach ($lxConfigPathes as $configPath) {
			$fullPath = $path . '/' . $configPath;
			if (file_exists($fullPath)) {
				return true;
			}
		}
		return false;
	}
}
