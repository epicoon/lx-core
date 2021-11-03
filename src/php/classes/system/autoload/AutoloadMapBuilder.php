<?php

namespace lx;

class AutoloadMapBuilder
{
	private array $packagesMap = [];
	private array $bootstrapFiles = [];
	private array $namespacesMap = [];
	private array $classes = [];
	private array $directories = [];

	/**
	 * Makes file 'autoload.json'
	 * Crowls all package directories (from application configuration with key 'packagesMap')
	 * Finds recursively in these directories for packages
	 * Package is a directory with special configuration file
	 */
	public function createCommonAutoloadMap(): void
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


	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * PRIVATE
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	private function save(): void
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

	private function analizeDirectory(string $path): void
	{
		$config = $this->tryGetPackageConfig($path);

		if ($config === null) {
			$dir = new Directory($path);
			$subDirs = $dir->getDirectoryNames();
			foreach ($subDirs as $subDir) {
				$this->analizeDirectory($path . '/' . $subDir);
			}
			return;
		}

		$this->analizePackage($path, $config->get());
	}

	private function analizePackage(string $packagePath, array $config): void
	{
		$packageName = $config['name'] ?? null;
        if ($packageName === null) {
            return;
        }

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

	private function tryGetPackageConfig(string $path): ?DataFileInterface
	{
		return (new PackageDirectory($path))->getConfigFile();
	}
}
