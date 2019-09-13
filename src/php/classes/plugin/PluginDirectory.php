<?php

namespace lx;

class PluginDirectory extends Directory {
	public function getConfigFile() {
		$configPathes = \lx::$conductor->getSystemPath('packageConfig');
		$path = $this->getPath();
		foreach ($configPathes as $configPath) {
			$fullPath = $path . '/' . $configPath;
			if (file_exists($fullPath)) {
				return new ConfigFile($fullPath);
			}
		}
		return null;
	}
}
