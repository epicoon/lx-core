<?php

namespace lx;

/**
 * @property-read string $sitePath
 * @property-read AutoloadMap $map
 */
class Autoloader
{
	const CLASS_MAIN_FILE = '_main';

	private string $_sitePath;
	private string $srcPath;
	private string $phpPath;
	private ?AutoloadMap $_map = null;
	private ?array $_systemClassMap = null;
	private static ?Autoloader $instance = null;

	private function __construct()
	{
	    // singletone
	}

	private function __clone()
	{
        // singletone
	}

	public static function getInstance(): Autoloader
	{
		if (self::$instance === null) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function init(string $sitePath, string $srcPath): void
	{
		$this->_sitePath = $sitePath;
		$this->srcPath = $srcPath;
		$this->phpPath = $this->srcPath . '/php';
		$this->_systemClassMap = require($this->phpPath . '/classMap.php');
		spl_autoload_register([$this, 'load']);

		$this->_map = new AutoloadMap($this->_sitePath);
	}

	/**
	 * @return AutoloadMap|string|null
	 */
	public function __get(string $name)
	{
		if ($name == 'sitePath') {
			return $this->_sitePath;
		}

		if ($name == 'map') {
			return $this->_map;
		}

		return null;
	}

	public function getClassPath(string $className): ?string
	{
		$path = $this->getSystemClassPath($className);
		if ($path !== null) {
		    return $path;
        }

		$path = $this->getClientClassPath($className);
		if ($path !== null) {
		    return $path;
        }

		$path = $this->getClassPathWithNamespaceMap($className);
		if ($path !== null) {
		    return $path;
        }

		$path = $this->getClassPathWithClassMap($className);
		if ($path !== null) {
		    return $path;
        }

		return null;
	}

	public function load(string $className): bool
	{
		$path = $this->getClassPath($className);

		if ($path === null) {
			return false;
		}

		require_once($path);
		return true;
	}


	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * PRIVATE
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	private function getSystemClassPath(string $className): ?string
	{
		$arr = explode('\\', $className);
		if (empty($arr) || count($arr) != 2 || $arr[0] != 'lx') {
			return null;
		}

		$name = $arr[1];
		$map = $this->_systemClassMap;
		if (array_key_exists($name, $map)) {
			$path = $this->phpPath . '/' . $map[$name] . '/' . $name . '.php';
			if (file_exists($path)) {
				return $path;
			}
		}

		return null;
	}

	private function getClientClassPath(string $className): ?string
	{
		$path = $this->sitePath . '/' . str_replace('\\', '/', $className) . '.php';
		if (file_exists($path)) {
			return $path;
		}

		return null;
	}

	private function getClassPathWithNamespaceMap(string $className): ?string
	{
		$namespaces = $this->map->namespaces;
		foreach ($namespaces as $namespace => $data) {
			$reg = '/^' . $namespace . '/';
			$reg = str_replace('\\', '\\\\', $reg);
			if (!preg_match($reg, $className)) {
				continue;
			}

			$subName = explode($namespace, $className)[1];
			$relativePath = str_replace('\\', '/', $subName);
			$basePath = $this->sitePath . '/' . $this->map->packages[$data['package']] . '/';
			foreach ($data['pathes'] as $innerPath) {
				$path = $basePath . $innerPath;
				if ($path[-1] != '/') $path .= '/';

				// Стандартная логика автозагрузки - имя файла соответствует имени класса
				$fullPath = $path . $relativePath . '.php';
				if (file_exists($fullPath)) {
					return $fullPath;
				}

				// Расширение логики автолоэйда до возможности использовать директории для классов
				$fullPath = $path . $relativePath . '/' . self::CLASS_MAIN_FILE . '.php';
				if (file_exists($fullPath)) {
					return $fullPath;
				}

				preg_match_all('/[^\\' . '\]+$/', $className, $matches);
				$propClassName = empty($matches[0]) ? '' : $matches[0][0];

				$fullPath = $path . $relativePath . '/_' . $propClassName . '.php';
				if (file_exists($fullPath)) {
					return $fullPath;
				}

				$fullPath = $path . $relativePath . '/' . $propClassName . '.php';
				if (file_exists($fullPath)) {
					return $fullPath;
				}
			}
		}

		return null;
	}

	private function getClassPathWithClassMap(string $className): ?string
	{
		$classes = $this->map->classes;
		if (array_key_exists($className, $classes)) {
			$path = $this->sitePath . '/' . $classes[$className]['path'];
			if (file_exists($path)) {
				return $path;
			}
		}

		return null;
	}
}
