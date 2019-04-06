<?php

namespace lx;

/*
	public function __construct($module)
	public function getModulePath()
	public function moduleContain($path)
	public function getFullPath($fileName)
	public function getPathInSite($fileName)
	public function getBlockPath($name = null)
	public function getInnerModulePath($moduleName)
	public function getScriptPath($name)
	public function getFile($name)
	public function findRespondent($name)
	public function getJsMain()
	public function getJsBootstrap()
	public function getCssAssets()
	public function getImageRoute($name)
	public function getConfigPath()
	public function getImagePathInSite()

	private function getSitePathReg()
	private function getRegFromPath($start, $path, $finish = '')
	private function decodeAlias($path)
	private function cutFullPathToSite($path)
*/
class ModuleConductor {
	private static $sitePathReg = null;
	private $module = null;

	/**
	 *
	 * */
	public function __construct($module) {
		$this->module = $module;
	}

	/**
	 *
	 * */
	public function getModulePath() {
		return $this->module->directory->getPath();
	}

	/**
	 *
	 * */
	public function moduleContain($path) {
		return $this->module->directory->contain($path);
	}

	/**
	 * Получить полное имя файла с учетом использования алиасов (модуля и приложения)
	 * */
	public function getFullPath($fileName) {
		if ($fileName{0} == '@') {
			$fileName = $this->decodeAlias($fileName);
		}

		return \lx::$conductor->getFullPath($fileName, $this->getModulePath());
	}

	/**
	 *
	 * */
	public function getPathInSite($fileName) {
		// Если начинается с '/' - это путь от корня сайта
		if ($fileName{0} == '/') return $fileName;

		$result = $this->getFullPath($fileName);
		$result = $this->cutFullPathToSite($result);
		return $result;
	}

	/**
	 * Если имя блока явно не задано, будет возвращен путь к корневому блоку модуля
	 * */
	public function getBlockPath($name = null) {
		if (!$name) $name = $this->module->getConfig('view');
		if (!$name) return null;

		if (!preg_match('/\.php$/', $name)) $name .= '.php';
		return $this->getFullPath($name);
	}

	/**
	 *
	 * */
	public function getInnerModulePath($moduleName) {
		$modulesDirectory = $this->module->getConfig('modulesDirectory');
		if ($modulesDirectory === null) return null;


		$path = $modulesDirectory == ''
			? $moduleName
			: $modulesDirectory . '/' . $moduleName;

		return $this->getFullPath($path);
	}

	/**
	 *
	 * */
	public function getScriptPath($name) {
		if (preg_match('/^http/', $name) || preg_match('/^\//', $name))
			return $name;

		return $this->getPathInSite($name);
	}

	/**
	 * Получить файл с учетом использования алиасов (модуля и приложения)
	 * */
	public function getFile($name) {
		$path = $this->getFullPath($name);
		if (!$path) return null;
		return BaseFile::getFileOrDir($path);
	}

	/**
	 * Вернет респондента
	 * */
	public function findRespondent($name) {
		$module = $this->module;

		$respondents = (array)$module->getConfig('respondents');
		if (!array_key_exists($name, $respondents)) {
			return false;
		}

		$namespace = $module->getConfig('respondentsNamespace');
		if (!$namespace) {
			$namespace = ClassHelper::getNamespace($module);
		}

		$className = $namespace . '\\' . $respondents[$name];

		if (!ClassHelper::exists($className)) {
			return false;
		}

		return new $className($module);
	}

	/**
	 *
	 * */
	public function getJsMain() {
		$jsMain = $this->module->getConfig('jsMain');
		if (!$jsMain) return null;
		return $this->getFile($jsMain);
	}

	/**
	 *
	 * */
	public function getJsBootstrap() {
		$jsBootstrap = $this->module->getConfig('jsBootstrap');
		if (!$jsBootstrap) return null;
		return $this->getFile($jsBootstrap);
	}

	/**
	 *
	 * */
	public function getCssAssets() {
		$result = [];

		$css = (array)$this->module->getConfig('css');
		foreach ($css as $value) {
			$path = $this->getFullPath($value);
			$d = new Directory($path);
			if (!$d->exists()) continue;

			$cssPath = $this->cutFullPathToSite($path);

			$files = $d->getAllFiles('*.css', Directory::FIND_NAME);
			$files->each(function($file) use (&$result, $cssPath) {
				$result[] = $cssPath . '/' . $file;
			});
		}

		return $result;
	}

	/**
	 *
	 * */
	public function getImageRoute($name) {
		return $this->getImagePathInSite() . '/' . $name;
	}

	/**
	 *
	 * */
	public function getConfigPath() {
		$pathes = \lx::$conductor->moduleConfig;

		// Получение собственных настроек
		foreach ($pathes as $path) {
			$fullPath = $this->getModulePath() . '/' . $path;
			if (file_exists($fullPath)) {
				return $fullPath;
			}
		}

		return null;
	}

	/**
	 * Путь к изображениям модуля относительно самого модуля
	 * Рендерер отсюда берет путь к изображениям
	 * Можно использовать @, / и {}
	 * */
	public function getImagePathInSite() {
		$images = $this->module->getConfig('images');
		if ($images === null) return false;

		return $this->getPathInSite($images);
	}

	/**
	 *
	 * */
	public function getImagePath() {
		$images = $this->module->getConfig('images');
		if ($images === null) return false;

		return $this->getFullPath($images);
	}

	/**
	 *
	 * */
	private function getSitePathReg() {
		if (self::$sitePathReg === null) {
			self::$sitePathReg = $this->getRegFromPath('^', \lx::sitePath());
		}
		return self::$sitePathReg;
	}

	/**
	 *
	 * */
	private function getRegFromPath($start, $path, $finish = '') {
		$modified = str_replace('/', '\/', $path);
		return "/$start$modified$finish/";
	}

	/**
	 *
	 * */
	private function decodeAlias($path) {
		$aliases = $this->module->getConfig('aliases');
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

	/**
	 *
	 * */
	private function cutFullPathToSite($path) {
		return explode(\lx::$conductor->site, $path)[1];
	}
}
