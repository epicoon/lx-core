<?php

namespace lx;

/**
 * Указывает на важные пути в архитектуре приложения
 * */
class ApplicationConductor extends Object {
	use ApplicationToolTrait;

	private $aliases = [];

	/**
	 * Геттер предоставляет доступ к полям, начинающимся с '_'
	 * */
	public function __get($name) {
		$result = parent::__get($name);
		if ($result !== null) {
			return $result;
		}

		$result = \lx::$conductor->$name;
		if ($result !== false) {
			return $result;
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

	public function getRootPath() {
		return \lx::$conductor->site;
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

	public function getRelativePath($path, $defaultLocation = null) {
		$fullPath = $this->getFullPath($path, $defaultLocation);
		return explode($this->sitePath . '/', $fullPath)[1];
	}

	/**
	 * Если путь начинается с '/' - он будет достроен путем от корня сайта
	 * Если путь начинается с '@' - он будет достроен расшифровкой алиаса
	 * Если путь начинается с '{package:package-name}' - он будет достроен относительно пакета
	 * Если путь начинается с '{service:service-name}' - он будет достроен относительно сервиса
	 * Если путь начинается с '{plugin:plugin-name}' - он будет достроен относительно плагина
	 * Если путь относительный - он достроится путем $defaultLocation
	 * Если $defaultLocation не задан - путь достроится от корня сайта
	 * */
	public function getFullPath($path, $defaultLocation = null) {
		if ($path{0} == '/') {
			if (preg_match('/^'. str_replace('/', '\/', $this->sitePath) .'/', $path)) return $path;
			return $this->sitePath . $path;
		}

		if ($path{0} == '@') {
			return $this->decodeAlias($path);
		}

		if ($path{0} == '{') {
			return $this->getStuffPath($path);
		}

		if ($defaultLocation === null) $defaultLocation = $this->sitePath;
		if ($defaultLocation[-1] != '/') $defaultLocation .= '/';
		return $defaultLocation . $path;
	}

	/**
	 *
	 * */
	public function getSystemPath($name) {
		return \lx::$conductor->getSystemPath($name);
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
	private function getStuffPath($path) {
		preg_match_all('/^{([^:]+?):([^}]+?)}\/?(.+?)$/', $path, $matches);
		if (empty($matches[1])) {
			return false;
		}

		$key = $matches[1][0];
		$name = $matches[2][0];
		$relativePath = $matches[3][0];
		if ($key == 'package') {
			$packagePath = $this->app->getPackagePath($name);
			if (!$packagePath) {
				return false;
			}

			return $packagePath . '/' . $relativePath;
		}

		if ($key == 'service') {
			$service = $this->app->getService($name);
			if (!$service) {
				return false;
			}

			return $service->getFilePath($relativePath);
		}

		if ($key == 'plugin') {
			$plugin = $this->app->getPlugin($name);
			if (!$plugin) {
				return false;
			}

			return $plugin->getFilePath($relativePath);
		}
	}
}
