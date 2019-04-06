<?php

namespace lx;

/**
 * Указывает на важные пути в архитектуре фреймворка
 * */
class Conductor {
	private
		// массив псевдонимов путей
		$aliases = [],
		$publicFields = [],

		// ключевые соглашения в путях
		$_site,
		$_system,
		$_lxFiles,
		$_lxData,
		$_lx,
		$_core,
		$_innerConfig,
		$_clientConfig,

		$_phpCore,
		$_jsCore,
		$_lxWidgets,
		$_stdResponses,

		$_classAutoloadMainFile,
		$_packageLxConfig,
		$_packageConfig,

		$_defaultServiceConfig,
		$_defaultModuleConfig;

	public static function innerConfigPath() {
		return dirname(__DIR__, 4) . '/config/main.php';
	}

	public function __construct($sitePath) {
		$this->_innerConfig = self::innerConfigPath();

		$this->_site = $sitePath;
		if (\lx::getType() == \lx::APP_TYPE_COMPOSER_PACKAGE) {
			$this->_lx = $this->_site . '/vendor/lx/lx-core';
			$this->_core = $this->_lx . '/src';
			$this->_lxFiles = $this->_site . '/lx';
			$this->_system = $this->_lxFiles . '/.system';
			$this->_lxData = $this->_lxFiles . '/data';
		} else {
			//todo - непакет не нужен вообще
			$this->_lx = $this->_site . '/lx';
			$this->_core = $this->_lx . '/_core';
			$this->_system = $this->_core . '/.system';
		}
		$this->_clientConfig = $this->_site . '/lx/config';

		$this->_phpCore = $this->_core . '/php';
		$this->_jsCore = $this->_core . '/js/app.js';
		$this->_lxWidgets = $this->_core . '/widgets';
		$this->_stdResponses = $this->_core . '/php/stdResponses';

		$this->_classAutoloadMainFile = '_main';
		$this->_packageLxConfig = ['lx-config.php', 'lx-config/main.php', 'lx-config.yaml', 'lx-config/main.yaml'];
		$this->_packageConfig = array_merge($this->_packageLxConfig, ['composer.json']);

		$this->_defaultServiceConfig = $this->_clientConfig . '/service.php';
		$this->_defaultModuleConfig = $this->_clientConfig . '/module.php';

		$this->publicFields = [
			'site',
			'lxFiles',
			'lxData'
		];
	}

	/**
	 * Геттер предоставляет доступ к полям, начинающимся с '_'
	 * */
	public function __get($name) {
		if (in_array($name, $this->publicFields)) {
			return $this->{'_' . $name};
		} elseif ($name == 'appConfig') {
			$config = $this->_clientConfig;
			if (file_exists($config . '/main.php')) {
				return $config . '/main.php';
			}
			if (file_exists($config . '/main.yaml')) {
				return $config . '/main.yaml';
			}
			throw new \Exception('Application configuration file not found', 400);
		} elseif ($name == 'autoloadMap') {
			return $this->_system . '/autoload.json';
		} elseif ($name == 'autoloadMapCache') {
			return $this->_system . '/autoloadCache.json';
		}

		if (!array_key_exists($name, $this->aliases)) {
			return false;
		}

		$alias = $this->aliases[$name];
		if (is_string($alias)) {
			return $this->getFullPath($alias);
		}

		return $alias;
	}

	/**
	 * Перезаписать список алиасов переданным массивом
	 * */
	public function setAliases($arr) {
		$this->aliases = [];
		$this->addAliases($arr);
	}

	/**
	 * Добавить к списку алиасов переданные в массиве
	 * */
	public function addAliases($arr) {
		foreach ($arr as $name => $path) {
			$this->addAlias($name, $path);
		}
	}

	/**
	 * Добавить алиас
	 * */
	public function addAlias($name, $path) {
		if (array_key_exists($name, $this->aliases) || property_exists($this, '_' . $name)) {
			throw new \Exception("Can not add alias '$name'. Alias with same name already exists", 400);
			
		}

		$this->aliases[$name] = $path;
	}

	/**
	 * Если путь начинается с '/' - он будет достроен путем от корня сайта
	 * Если путь начинается с '@' - он будет достроен расшифровкой алиаса
	 * Если путь начинается с '{package:package-name}' - он будет достроен относительно пакета
	 * Если путь начинается с '{service:service-name}' - он будет достроен относительно сервиса
	 * Если путь начинается с '{module:module-name}' - он будет достроен относительно модуля
	 * Если путь относительный - он достроится путем $defaultLocation
	 * Если $defaultLocation не задан - путь достроится от корня сайта
	 * */
	public function getFullPath($path, $defaultLocation = null) {
		if ($path{0} == '/') {
			if (preg_match('/^'. str_replace('/', '\/', $this->site) .'/', $path)) return $path;
			return $this->site . $path;
		}

		if ($path{0} == '@') {
			return $this->decodeAlias($path);
		}

		if ($path{0} == '{') {
			return $this->getRelativePath($path);
		}

		if ($defaultLocation === null) $defaultLocation = $this->site;
		return $defaultLocation . '/' . $path;
	}

	/**
	 *
	 * */
	public function getSystemPath($name) {
		if (property_exists($this, '_' . $name)) {
			return $this->{'_' . $name};
		}

		return false;
	}

	// /**
	//  * Получить путь по алиасу
	//  * */
	// private function getAlias($name) {
	// 	$name = preg_replace('/^@/', '', $name);
	// 	if (array_key_exists($name, $this->aliases)) return $this->aliases[$name];
	// 	$name = '_' . $name;
	// 	if (property_exists($this, $name)) return $this->$name;
	// 	return null;
	// }

	/**
	 * Расшифровывает алиас в пути, согласно своему списку алиасов
	 * Если в списке алиас не найден - проверит свои поля, н-р для алиаса '@lx' подставит значение $this->_lx
	 * */
	private function decodeAlias($path) {
		preg_match_all('/^@([_\w][_\-\w\d]*?)(\/|$)/', $path, $arr);
		if (empty($arr[0])) return $path;

		$mask = $arr[0][0];
		$alias = $arr[1][0];

		// Если алиас не задан в массиве - будет проверено поле самого кондуктора
		if (array_key_exists($alias, $this->aliases)) $alias = $this->aliases[$alias];
		else $alias = $this->$alias;

		//todo возможно надо логировать проблему, или выбросить исключение
		if (!$alias) return false;

		// Вторая группа может содержать слэш
		$alias .= $arr[2][0];
		$path = str_replace($mask, $alias, $path);

		if ($path{0} == '@') return $this->decodeAlias($path);
		return $path;
	}

	/**
	 *
	 * */
	private function getRelativePath($path) {
		preg_match_all('/^{([^:]+?):([^}]+?)}\/?(.+?)$/', $path, $matches);
		if (empty($matches[1])) {
			return false;
		}

		$key = $matches[1][0];
		$name = $matches[2][0];
		$relativePath = $matches[3][0];
		if ($key == 'package') {
			$packagePath = \lx::getPackagePath($name);
			if (!$packagePath) {
				return false;
			}

			return $packagePath . '/' . $relativePath;
		}

		if ($key == 'service') {
			$service = \lx::getService($name);
			if (!$service) {
				return false;
			}

			return $service->getFilePath($relativePath);
		}

		if ($key == 'module') {
			$module = \lx::getModule($name);
			if (!$module) {
				return false;
			}

			return $module->getFilePath($relativePath);
		}
	}
}
