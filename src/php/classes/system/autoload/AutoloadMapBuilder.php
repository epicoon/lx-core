<?php

namespace lx;

/*
Дока по настройкам композера
https://getcomposer.org/doc/04-schema.md
*/
class AutoloadMapBuilder {
	private $packagesMap = [];
	private $bootstrapFiles = [];
	private $namespacesMap = [];
	private $classes = [];
	private $directories = [];

	/**
	 * Формирование файла 'autoload.json'
	 * Проходит по карте директорий с пакетами (из конфига приложения по ключу 'packagesMap')
	 * Рекурсивно в директориях ищет пакеты
	 * Пакетом считается каталог, в котором удалось найти конфигурационный файл
	 * */
	public function createCommonAutoloadMap() {
		$map = \lx::getConfig('packagesMap');

		foreach ($map as $dirPath) {
			$fullDirPath = \lx::$conductor->getFullPath($dirPath);
			if (!file_exists($fullDirPath) || !is_dir($fullDirPath)) {
				continue;
			}

			$this->analizeDirectory($fullDirPath);
		}

		$this->save();
	}

	/**
	 * Сохранение в файл всех собранных карт
	 * */
	private function save() {
		$data = [
			'packages' => $this->packagesMap,
			'files' => $this->bootstrapFiles,
			'namespaces' => $this->namespacesMap,
			'classes' => $this->classes,
			'directories' => $this->directories,
		];
		$data = json_encode($data);
		$file = new File(\lx::$conductor->autoloadMap);
		$file->put($data);
	}

	/**
	 * Проверяем - по данному пути находится пакет или нет
	 * Если пакет - строятся карты
	 * Если не пакет - рекурсия по подкаталогам
	 * Пакетом признается директория, у которой есть конфигурационный файл:
	 * - (lx-config|lx-config/main).(yaml|php) || composer.json
	 * */
	private function analizeDirectory($path) {
		$config = $this->tryGetPackageConfig($path);

		if ($config === null) {
			$dir = new Directory($path);
			$subDirs = $dir->getDirs(Directory::FIND_NAME);
			$subDirs = $subDirs->getData();
			foreach ($subDirs as $subDir) {
				$this->analizeDirectory($path . '/' . $subDir);
			}
			return;
		}

		$this->analizePackage($path, $config->get());
	}

	/**
	 * Анализ конфига пакета - строятся карты
	 * */
	private function analizePackage($packagePath, $config) {
		// Карта пакетов
		$packageName = $config['name'];
		$relativePackagePath = explode(\lx::sitePath() . '/', $packagePath)[1];

		$this->packagesMap[$packageName] = $relativePackagePath;

		// Автозагрузка
		if (!isset($config['autoload'])) {
			return;
		}
		$autoload = $config['autoload'];

		// Файлы для подключения сразу и всегда
		if (isset($autoload['files'])) {
			$files = (array)$autoload['files'];
			foreach ($files as &$file) {
				$file = $relativePackagePath . '/' . $file;
			}
			unset($file);
			$this->bootstrapFiles = array_merge($this->bootstrapFiles, $files);
		}

		// PSR-4 и PSR-0
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

		// Карта каталогов для финального поиска
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
	 * Пытается извлечь конфиг пакета из директории. Если не извлекает - вернет null, значит это не пакет
	 * */
	private function tryGetPackageConfig($path) {
		return (new PackageDirectory($path))->getConfigFile();
	}
}
