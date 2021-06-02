<?php

namespace lx;

/**
 * Class AutoloadMapBuilder
 * @package lx
 */
class AutoloadMapBuilder
{
	/** @var array */
	private $packagesMap = [];

	/** @var array */
	private $bootstrapFiles = [];

	/** @var array */
	private $namespacesMap = [];

	/** @var array */
	private $classes = [];

	/** @var array */
	private $directories = [];

	/**
	 * Makes file 'autoload.json'
	 * Crowls all package directories (from application configuration with key 'packagesMap')
	 * Finds recursively in these directories for packages
	 * Package is a directory with special configuration file
	 */
	public function createCommonAutoloadMap()
	{
		$map = \lx::$app->getConfig('packagesMap');

		foreach ($map as $dirPath) {
			$fullDirPath = \lx::$app->conductor->getFullPath($dirPath);
			if (!file_exists($fullDirPath) || !is_dir($fullDirPath)) {
				continue;
			}

			$this->analizeDirectory($fullDirPath);
		}

		$this->save();
	}


	/*******************************************************************************************************************
	 * PRIVATE
	 ******************************************************************************************************************/

	/**
	 * Save all maps to file
	 */
	private function save()
	{
		$data = [
			'packages' => $this->packagesMap,
			'files' => $this->bootstrapFiles,
			'namespaces' => $this->namespacesMap,
			'classes' => $this->classes,
			'directories' => $this->directories,
		];
		$data = json_encode($data);
		$file = new File(\lx::$conductor->getSystemPath('autoload.json'));
		$file->put($data);
	}

	/**
	 * @param string $path
	 */
	private function analizeDirectory($path)
	{
		$config = $this->tryGetPackageConfig($path);

		if ($config === null) {
			$dir = new Directory($path);
			$subDirs = $dir->getDirectoryNames();
			$subDirs = $subDirs->toArray();
			foreach ($subDirs as $subDir) {
				$this->analizeDirectory($path . '/' . $subDir);
			}
			return;
		}

		$this->analizePackage($path, $config->get());
	}

	/**
	 * @param string $packagePath
	 * @param array $config
	 */
	private function analizePackage($packagePath, $config)
	{
		$packageName = $config['name'];
		$relativePackagePath = explode(\lx::$app->sitePath . '/', $packagePath)[1];

		$this->packagesMap[$packageName] = $relativePackagePath;

		if (!isset($config['autoload'])) {
			return;
		}
		$autoload = $config['autoload'];

		if (isset($autoload['files'])) {
			$files = (array)$autoload['files'];
			foreach ($files as &$file) {
				$file = $relativePackagePath . '/' . $file;
			}
			unset($file);
			$this->bootstrapFiles = array_merge($this->bootstrapFiles, $files);
		}

		if (isset($autoload['psr-4'])) {
			foreach ($autoload['psr-4'] as $namespace => $pathes) {
				$this->namespacesMap[$namespace] = [
					'package' => $packageName,
					'pathes' => (array)$pathes
				];
			}
		}
		if (isset($autoload['psr-0'])) {
			foreach ($autoload['psr-0'] as $namespace => $pathes) {
				$this->namespacesMap[$namespace] = [
					'package' => $packageName,
					'pathes' => (array)$pathes
				];
			}
		}

		if (isset($autoload['classmap'])) {
			$classmap = (array)$autoload['classmap'];
			foreach ($classmap as $item) {
				$item = $relativePackagePath . '/' . $item;
				if (preg_match('/\.php$/', $item)) {
					$this->classes[] = [
						'package' => $packageName,
						'path' => $item,
					];
				} else {
					$this->directories[] = [
						'package' => $packageName,
						'path' => $item,
					];
				}
			}
		}
	}

	/**
	 * @param string $path
	 * @return DataFileInterface|null
	 */
	private function tryGetPackageConfig($path)
	{
		return (new PackageDirectory($path))->getConfigFile();
	}
}
