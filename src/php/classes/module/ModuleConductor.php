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
	public function getModuleFrontendDirectory()
	public function getJsFile($name)
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
		// Если начинается с '/' - это путь от корня сайта
		if ($fileName{0} == '/') return \lx::$conductor->site . $fileName;

		if ($fileName{0} == '@') {
			$path = $this->decodeAlias($fileName);
			if ($path{0} == '@') {
				$path = \lx::$conductor->decodeAlias($path);
				if ($path === false) return false;
				return $path;
			}
			$fileName = $path;
		}

		// Если это уже полный путь
		if (preg_match($this->getSitePathReg(), $fileName)) return $fileName;

		return $this->getModulePath() . '/' . $fileName;
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
		if (!$name) $name = $this->module->getConfig('viewIndex');
		if (!$name) return null;

		if (!preg_match('/\.php$/', $name)) $name .= '.php';

		if ($name{0} == '@') return $this->getFullPath($name);

		$dir = $this->module->getConfig('view');
		if (!$dir) return null;
		if ($dir{0} == '@') return $this->getFullPath($dir) . '/' . $name;
		
		$path = "$dir/$name";

		if (!$this->moduleContain($path)) return null;
		return $this->getModulePath() . '/'. $path;
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
		$service = $module->getService();

		$respondents = (array)$module->getConfig('respondents');

		//todo - привести все в порядок. Сейчас нет нормального протокола объявления респондентов
		if (array_key_exists($name, $respondents)) {
			$className = $respondents[$name];
			return new $className($module);
		}

		$servicePath = $service->dir->getPath();
		$modulePath = $this->getModulePath();

		$namespacePrefixes = $service->getConfig('autoload.psr-4');
		foreach ($namespacePrefixes as $namespacePrefix => $innerPath) {
			$basePath = $innerPath == ''
				? $servicePath . '/'
				: $servicePath . '/' . $innerPath . '/';

			//todo - при заимствовании модуля другим сервисом тут получается хрень
			$relativePath = explode($basePath, $modulePath)[1];

			foreach ($respondents as $path) {
				$fullPath = $relativePath . '/' . $path;
				$namespace = $namespacePrefix . str_replace('/', '\\', $fullPath);
				$className = $namespace . '\\' . $name;
				if (ClassHelper::exists($className)) {
					return new $className($module);
				}
			}
		}

		return false;
	}

	/**
	 * Если есть фронтенд-директория для модуля - будет возвращен объект-директория
	 * */
	public function getModuleFrontendDirectory() {
		$frontend = $this->module->getConfig('frontend');
		if ($frontend === null) return null;

		return $this->getFile($frontend);
	}

	/**
	 *
	 * */
	public function getJsFile($name) {
		if ($name{0} == '@') return $this->getFile($name);

		$frontend = $this->getModuleFrontendDirectory();
		if (!$frontend) return null;
		return $frontend->get($name);
	}

	/**
	 *
	 * */
	public function getJsMain() {
		$jsMain = $this->module->getConfig('jsMain');
		if (!$jsMain) return null;
		return $this->getJsFile($jsMain);
	}

	/**
	 *
	 * */
	public function getJsBootstrap() {
		$jsBootstrap = $this->module->getConfig('jsBootstrap');
		if (!$jsBootstrap) return null;
		return $this->getJsFile($jsBootstrap);
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
	 * Можно использовать алиасы
	 * Можно использовать путь другого модуля, указав 'images' => '{module: moduleName}'
	 * */
	public function getImagePathInSite() {
		$images = $this->module->getConfig('images');
		if ($images === null) return false;

		preg_match_all('/^{([^\s]+?)\s*:\s*([^\s]+?)}$/', $images, $arr);
		$key = empty($arr[1]) ? null : $arr[1][0];
		$value = empty($arr[2]) ? null : $arr[2][0];
		if ($key !== null && $value !== null) {
			if ($key == 'module') return \lx::getModule($value)->conductor->getImagePathInSite();
		}

		return $this->getPathInSite($images);
	}

	/**
	 *
	 * */
	public function getImagePath() {
		$images = $this->module->getConfig('images');
		if ($images === null) return false;

		preg_match_all('/^{([^\s]+?)\s*:\s*([^\s]+?)}$/', $images, $arr);
		$key = empty($arr[1]) ? null : $arr[1][0];
		$value = empty($arr[2]) ? null : $arr[2][0];
		if ($key !== null && $value !== null) {
			if ($key == 'module') return \lx::getModule($value)->conductor->getImagePath();
		}

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
