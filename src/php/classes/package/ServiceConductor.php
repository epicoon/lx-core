<?php

namespace lx;

/**
 * Class ServiceConductor
 * @package lx
 */
class ServiceConductor extends BaseObject implements ConductorInterface, FusionComponentInterface
{
	use FusionComponentTrait;

	/**
	 * @return Service
	 */
	public function getService()
	{
		return $this->owner;
	}

	/**
	 * @return string
	 * */
	public function getPath()
	{
		return $this->getService()->directory->getPath();
	}

	/**
	 * @param string $fileName - path relative to the service root
	 * @param string $relativePath
	 * @return string
	 */
	public function getFullPath($fileName, $relativePath = null)
	{
		if ($fileName{0} == '@') {
			$fileName = $this->decodeAlias($fileName);
		}

		if ($relativePath === null) {
			$relativePath = $this->getPath();
		}

		return $this->getService()->app->conductor->getFullPath($fileName, $relativePath);
	}

	/**
	 * @param string $path
	 * @param string $defaultLocation
	 * @return string
	 */
	public function getRelativePath($path, $defaultLocation = null)
	{
		$fullPath = $this->getFullPath($path, $defaultLocation);
		return explode($this->getPath() . '/', $fullPath)[1];
	}

	/**
	 * @param string $name
	 * @return BaseFile|null
	 */
	public function getFile($name)
	{
		$path = $this->getFullPath($name);
		if (!$path) {
			return null;
		}

		return BaseFile::construct($path);
	}

	/**
	 * @return string
	 */
	public function getSystemPath($name = null)
	{
		$path = $this->getPath() . '/.system';
		if ($name) {
			return $path . '/' . $name;
		}

		return $path;
	}

	/**
	 * @param string $pluginName
	 * @return string|null
	 * */
	public function getPluginPath($pluginName)
	{
		$pluginDirs = (array)$this->getService()->getConfig('service.plugins');
		foreach ($pluginDirs as $dir) {
			if ($dir != '' && $dir{-1} != '/') {
				$dir .= '/';
			}
			$fullPath = $this->getPath() . '/' . $dir . $pluginName;

			if (file_exists($fullPath)) {
				return $fullPath;
			}
		}

		return null;
	}

	/**
	 * @return string|false
	 */
	public function getDefaultModelPath()
	{
		$models = $this->getService()->getConfig('service.models');
		if ($models === null) {
			return false;
		}

		$models = (array)$models;
		return $this->getFullPath($models[0]);
	}

	/**
	 * @param string $name
	 * @return string|false
	 */
	public function getModelPath($name)
	{
		$modelsMap = $this->getService()->getConfig('service.modelsMap');
		if ($modelsMap !== null) {
			if (array_key_exists($name, $modelsMap)) {
				$fullPath = $this->getFullPath($modelsMap[$name]);
				if (file_exists($fullPath)) {
					return $fullPath;
				}
			}
		}

		$models = $this->getService()->getConfig('service.models');
		if ($models === null) {
			return false;
		}

		$models = (array)$models;

		foreach ($models as $dir) {
			$path = $this->getFullPath($dir);
			$d = new Directory($path);
			$f = $d->find($name . '.yaml', Directory::FIND_NAME);
			if ($f) {
				return $f;
			}
		}

		return false;
	}

	/**
	 * @return array
	 */
	public function getModelsPath()
	{
		$result = [];

		$modelsMap = $this->getService()->getConfig('service.modelsMap');
		if ($modelsMap !== null) {
			$result = $modelsMap;
		}

		$models = $this->getService()->getConfig('service.models');
		if ($models === null) return $result;

		$models = (array)$models;

		foreach ($models as $dir) {
			$path = $this->getFullPath($dir);
			if (!file_exists($path)) {
				continue;
			}
			$d = new Directory($path);

			$ff = $d->getContent([
				'findType' => Directory::FIND_NAME,
				'mask' => '*.yaml',
				'ext' => false
			]);
			$ff->each(function ($a) use ($path, &$result) {
				$result[$a] = "$path/$a.yaml";
			});
		}

		return $result;
	}

	/**
	 * @return Directory
	 */
	public function getMigrationDirectory()
	{
		$dir = new Directory($this->getSystemPath('migrations'));
		$dir->make();
		return $dir;
	}

	/**
	 * @return Directory
	 */
	public function getModuleMapDirectory()
	{
		$dir = new Directory($this->getSystemPath('modules'));
		$dir->make();
		return $dir;
	}

	/**
	 * @param string $path
	 * @return string
	 */
	private function decodeAlias($path)
	{
		$aliases = $this->getService()->getConfig('service.aliases');
		if (!$aliases) return $path;

		$result = $path;
		while (true) {
			preg_match_all('/^@([_\w][_\-\w\d]*?)(\/|$)/', $result, $arr);
			if (empty($arr) || empty($arr[0])) return $result;

			$mask = $arr[0][0];
			$alias = $arr[1][0];
			if (!array_key_exists($alias, $aliases)) return $result;

			$alias = $aliases[$alias] . $arr[2][0];
			$result = str_replace($mask, $alias, $result);
		}
	}
}
