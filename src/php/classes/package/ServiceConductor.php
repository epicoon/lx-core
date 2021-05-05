<?php

namespace lx;

use lx;

class ServiceConductor implements ConductorInterface, FusionComponentInterface
{
    use ObjectTrait;
	use FusionComponentTrait;

	public function getService(): Service
	{
		return $this->owner;
	}

	public function getPath(): string
	{
		return $this->getService()->directory->getPath();
	}

	public function getFullPath(string $fileName, ?string $relativePath = null): ?string
	{
		if ($fileName[0] == '@') {
			$fileName = $this->decodeAlias($fileName);
		}

		if ($relativePath === null) {
			$relativePath = $this->getPath();
		}

		return $this->getService()->app->conductor->getFullPath($fileName, $relativePath);
	}

	public function getRelativePath(string $path, ?string $defaultLocation = null): string
	{
		$fullPath = $this->getFullPath($path, $defaultLocation);
		return explode($this->getPath() . '/', $fullPath)[1];
	}

	public function getFile(string $name): ?BaseFile
	{
		$path = $this->getFullPath($name);
		if (!$path) {
			return null;
		}

		return BaseFile::construct($path);
	}

	public function getSystemPath(?string $name = null): string
	{
		$path = $this->getPath() . '/.system';
		if ($name) {
			return $path . '/' . $name;
		}

		return $path;
	}

    public function getLocalSystemPath(?string $name = null): string
    {
        $path = lx::$conductor->getSystemPath('services') . '/'
            . lx::$app->conductor->getRelativePath($this->getPath(), lx::$app->sitePath);

        return $path;
    }

	public function getPluginPath(string $pluginName): ?string
	{
		$pluginDirs = (array)$this->getService()->getConfig('plugins');
		foreach ($pluginDirs as $dir) {
			if ($dir != '' && $dir[-1] != '/') {
				$dir .= '/';
			}
			$fullPath = $this->getPath() . '/' . $dir . $pluginName;

			if (file_exists($fullPath)) {
				return $fullPath;
			}
		}

		return null;
	}

    public function getModuleMapDirectory(): Directory
    {
        $dir = new Directory($this->getSystemPath('modules'));
        $dir->make();
        return $dir;
    }
    
	private function decodeAlias(string $path): string
	{
		$aliases = $this->getService()->getConfig('aliases');
		if (!$aliases) return $path;

		$result = $path;
		while (true) {
			preg_match_all('/^@([_\w][_\-\w\d]*?)(\/|$)/', $result, $arr);
			if (empty($arr) || empty($arr[0])) {
			    return $result;
            }

			$mask = $arr[0][0];
			$alias = $arr[1][0];
			if (!array_key_exists($alias, $aliases)) {
			    return $result;
            }

			$alias = $aliases[$alias] . $arr[2][0];
			$result = str_replace($mask, $alias, $result);
		}
	}
}
