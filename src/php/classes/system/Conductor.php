<?php

namespace lx;

/**
 * Указывает на важные пути в архитектуре фреймворка
 * */
class Conductor {
	private
		// массив псевдонимов путей
		$aliases = [],

		// ключевые соглашения в путях
		$_site,
		$_systemDirectory,
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
			$this->_systemDirectory = $this->_site . '/lx/.system';
		} else {
			$this->_lx = $this->_site . '/lx';
			$this->_core = $this->_lx . '/_core';
			$this->_systemDirectory = $this->_core . '/.system';
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
	}

	/**
	 * Геттер предоставляет доступ к полям, начинающимся с '_'
	 * */
	public function __get($name) {
		if ($name == 'appConfig') {
			$config = $this->_clientConfig;
			if (file_exists($config . '/main.php')) {
				return $config . '/main.php';
			}
			if (file_exists($config . '/main.yaml')) {
				return $config . '/main.yaml';
			}
			throw new \Exception('Application configuration file not found', 400);
		} elseif ($name == 'autoloadMap') {
			return $this->_systemDirectory . '/autoload.json';
		} elseif ($name == 'autoloadMapCache') {
			return $this->_systemDirectory . '/autoloadCache.json';
		}

		$alias = $this->getAlias($name);
		if (!$alias) return null;
		if (is_array($alias)) return $alias;
		return $this->decodeAlias($alias);
	}

	/**
	 * Перезаписать список алиасов переданным массивом
	 * */
	public function setAliases($arr) {
		$this->aliases = $arr;
	}

	/**
	 * Добавить к списку алиасов переданные в массиве
	 * */
	public function addAliases($arr) {
		$this->aliases += $arr;
	}

	/**
	 * Добавить алиас
	 * */
	public function addAlias($name, $path) {
		$this->aliases[$name] = $path;
	}

	/**
	 * Получить путь по алиасу
	 * */
	public function getAlias($name) {
		$name = preg_replace('/^@/', '', $name);
		if (array_key_exists($name, $this->aliases)) return $this->aliases[$name];
		$name = '_' . $name;
		if (property_exists($this, $name)) return $this->$name;
		return null;
	}

	/**
	 * Расшифровывает алиас в пути, согласно своему списку алиасов
	 * Если в списке алиас не найден - проверит свои поля, н-р для алиаса '@lx' подставит значение $this->_lx
	 * */
	public function decodeAlias($path) {
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
	 * Если путь начинается с '@' или '/' - он будет достроен соответственно расшифровкой алиаса или путем от корня сайта
	 * Если путь относительный - он достроится путем $defaultLocation
	 * Если $defaultLocation не задан - путь достроится от корня сайта
	 * */
	public function getFullPath($path, $defaultLocation = null) {
		if ($path{0} == '/') {
			if (preg_match('/^'. str_replace('/', '\/', $this->site) .'/', $path)) return $path;
			return $this->site . $path;
		}
		if ($path{0} == '@') return $this->decodeAlias($path);

		if ($defaultLocation === null) $defaultLocation = $this->site;
		return $defaultLocation . '/' . $path;
	}
}
