<?php

namespace lx;

class AutoloadMapBuilder
{
	private array $servicesMap = [];
	private array $bootstrapFiles = [];
	private array $namespacesMap = [];
	private array $classes = [];
	private array $directories = [];

	/**
	 * Makes file 'autoload.json'
	 * Crowls all service directories
	 * Finds recursively in these directories for services
	 * Package is a directory with special configuration file
	 */
	public function createCommonAutoloadMap(): void
	{
		$map = \lx::$app->serviceProvider->getCategories();

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
			'services' => $this->servicesMap,
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
		$serviceName = $config['name'] ?? null;
        if ($serviceName === null) {
            return;
        }

		$relativePackagePath = explode(\lx::$app->sitePath . '/', $packagePath)[1];

		$this->servicesMap[$serviceName] = $relativePackagePath;

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
					'package' => $serviceName,
					'pathes' => (array)$pathes
				];
			}
		}
		if (isset($autoload['psr-0'])) {
			foreach ($autoload['psr-0'] as $namespace => $pathes) {
				$this->namespacesMap[$namespace] = [
					'package' => $serviceName,
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
						'package' => $serviceName,
						'path' => $item,
					];
				} else {
					$this->directories[] = [
						'package' => $serviceName,
						'path' => $item,
					];
				}
			}
		}
	}

	private function tryGetPackageConfig(string $path): ?DataFileInterface
	{
		return (new ServiceDirectory($path))->getConfigFile();
	}
}
