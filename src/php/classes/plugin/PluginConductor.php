<?php

namespace lx;

/*
	public function __construct($plugin)
	public function getPath()
	public function pluginContain($path)
	public function getFullPath($fileName)
	public function getPathInSite($fileName)
	public function getSnippetPath($name = null)
	public function getScriptPath($name)
	public function getFile($name)
	public function findRespondent($name)
	public function getJsMain()
	public function getJsBootstrap()
	public function getCssAssets()
	public function getImageRoute($name)
	public function getConfigPath()
	public function getImagePathInSite()

	private function getRegFromPath($start, $path, $finish = '')
	private function decodeAlias($path)
	private function cutFullPathToSite($path)
*/
class PluginConductor {
	private $app;
	private $plugin;

	/**
	 *
	 * */
	public function __construct($plugin) {
		$this->plugin = $plugin;
		$this->app = $plugin->app;
	}

	public function getRootPath() {
		return $this->getPath();
	}

	/**
	 *
	 * */
	public function getPath() {
		return $this->plugin->directory->getPath();
	}

	public function getSystemPath() {
		return $this->getPath() . '/.system';
	}

	/**
	 *
	 * */
	public function pluginContain($path) {
		return $this->plugin->directory->contains($path);
	}

	/**
	 * Получить полное имя файла с учетом использования алиасов (плагина и приложения)
	 * */
	public function getFullPath($fileName, $relativePath = null) {
		if ($fileName{0} == '@') {
			$fileName = $this->decodeAlias($fileName);
		}
		
		if ($relativePath === null) {
			$relativePath = $this->getPath();
		}

		return $this->app->conductor->getFullPath($fileName, $relativePath);
	}

	public function getRelativePath($path, $defaultLocation = null) {
		$fullPath = $this->getFullPath($path, $defaultLocation);
		return explode($this->getPath() . '/', $fullPath)[1];
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
	 * Если имя блока явно не задано, будет возвращен путь к корневому блоку плагина
	 * */
	public function getSnippetPath($name = null) {
		if ($name === null) {
			return $this->getFullPath($this->plugin->getConfig('rootSnippet'));
		}

		$snippetDirs = $this->plugin->getConfig('snippets');
		if (!$snippetDirs) {
			return null;
		}

		foreach ((array)$snippetDirs as $snippetDir) {
			$path = $this->getFullPath($snippetDir . '/' . $name);

			if (file_exists($path)) return $path;
			if (file_exists("$path.js")) return "$path.js";
			if (file_exists("$path/_main.js")) return "$path/_main.js";
			if (file_exists("$path/main.js")) return "$path/main.js";

			$arr = explode('/', $path);
			$snippetName = end($arr);
			if (file_exists("$path/_$snippetName.js")) return "$path/_$snippetName.js";
			if (file_exists("$path/$snippetName.js")) return "$path/$snippetName.js";
		}

		return null;
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
	 * Получить файл с учетом использования алиасов (плагина и приложения)
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
		$plugin = $this->plugin;

		$respondents = (array)$plugin->getConfig('respondents');
		if (!array_key_exists($name, $respondents)) {
			return false;
		}

		$namespace = $plugin->getConfig('respondentsNamespace');
		if (!$namespace) {
			$namespace = ClassHelper::getNamespace($plugin);
		}

		$className = $namespace . '\\' . $respondents[$name];

		if (!ClassHelper::exists($className)) {
			return false;
		}

		return new $className($plugin);
	}

	/**
	 *
	 * */
	public function getJsMain() {
		$jsMain = $this->plugin->getConfig('jsMain');
		if (!$jsMain) return null;
		return $this->getFile($jsMain);
	}

	/**
	 *
	 * */
	public function getJsBootstrap() {
		$jsBootstrap = $this->plugin->getConfig('jsBootstrap');
		if (!$jsBootstrap) return null;
		return $this->getFile($jsBootstrap);
	}

	/**
	 *
	 * */
	public function getCssAssets() {
		$result = [];

		$css = (array)$this->plugin->getConfig('css');
		foreach ($css as $value) {
			$path = $this->getFullPath($value);
			$d = new Directory($path);
			if (!$d->exists()) continue;

			$files = $d->getFiles('*.(css|css.js)');
			$pares = [];
			$files->each(function($file) use (&$pares) {
				$ext = $file->getExtension();
				$key = $ext == 'css'
					? $file->getName()
					: $file->getCleanName();
				if (!array_key_exists($key, $pares)) {
					$pares[$key] = [
						'js' => null,
						'css' => null,
					];
				}
				if ($ext == 'css') {
					$pares[$key]['css'] = $file;
				} else {
					$pares[$key]['js'] = $file;
				}
			});

			$cssPath = $this->cutFullPathToSite($path);
			foreach ($pares as $key => $pare) {
				if ($pare['js']
					&& (!$pare['css'] || $pare['js']->isNewer($pare['css']))
				) {
					if (!$pare['css']) {
						$pare['css'] = $d->makeFile($key);
					}
					$exec = new NodeJsExecutor();
					$cssCode = $exec->runFile($pare['js'], ['@core/js/classes/css/CssContext']);
					$pare['css']->put($cssCode);
				}

				$result[] = $cssPath . '/' . $pare['css']->getName();
			}
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
		$pathes = $this->app->conductor->pluginConfig;

		// Получение собственных настроек
		foreach ($pathes as $path) {
			$fullPath = $this->getPath() . '/' . $path;
			if (file_exists($fullPath)) {
				return $fullPath;
			}
		}

		return null;
	}

	/**
	 * Путь к изображениям плагина относительно самого плагина
	 * Рендерер отсюда берет путь к изображениям
	 * Можно использовать @, / и {}
	 * */
	public function getImagePathInSite() {
		$images = $this->plugin->getConfig('images');
		if ($images === null) return false;

		return $this->getPathInSite($images);
	}

	/**
	 *
	 * */
	public function getImagePath() {
		$images = $this->plugin->getConfig('images');
		if ($images === null) return false;

		return $this->getFullPath($images);
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
		$aliases = $this->plugin->getConfig('aliases');
		if (!$aliases) {
			return $this->plugin->getService()->getFilePath($path);
		}

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
		return explode($this->app->sitePath, $path)[1];
	}
}
