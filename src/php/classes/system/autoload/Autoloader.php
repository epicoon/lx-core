<?php

namespace lx;

/**
 * Class Autoloader
 * @package lx
 *
 * @property-read string $sitePath
 * @property-read AutoloadMap $map
 */
class Autoloader
{
	const CLASS_MAIN_FILE = '_main';

	/** @var string */
	private $_sitePath;

	/** @var string */
	private $srcPath;

	/** @var string */
	private $phpPath;

	/** @var AutoloadMap */
	private $_map = null;

	/** @var array */
	private $_systemClassMap = null;

	/** @var Autoloader */
	private static $instance = null;

	/**
	 * Autoloader constructor is private
	 */
	private function __construct()
	{
	}

	/**
	 * Clone magic method is private
	 */
	private function __clone()
	{
	}

	/**
	 * @return Autoloader
	 */
	public static function getInstance()
	{
		if (self::$instance === null) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @param string $sitePath
	 * @param string $srcPath
	 */
	public function init($sitePath, $srcPath)
	{
		$this->_sitePath = $sitePath;
		$this->srcPath = $srcPath;
		$this->phpPath = $this->srcPath . '/php';
		$this->_systemClassMap = require($this->phpPath . '/classMap.php');
		spl_autoload_register([$this, 'load']);

		$this->_map = new AutoloadMap($this->_sitePath);
	}

	/**
	 * @param string $name
	 * @return AutoloadMap|string|null
	 */
	public function __get($name)
	{
		if ($name == 'sitePath') {
			return $this->_sitePath;
		}

		if ($name == 'map') {
			return $this->_map;
		}

		return null;
	}

	/**
	 * @param string $className
	 * @return string|false
	 */
	public function getClassPath($className)
	{
		$path = $this->getSystemClassPath($className);
		if ($path !== false) return $path;

		$path = $this->getClientClassPath($className);
		if ($path !== false) return $path;

		$path = $this->getClassPathWithNamespaceMap($className);
		if ($path !== false) return $path;

		$path = $this->getClassPathWithClassMap($className);
		if ($path !== false) return $path;

		return false;
	}

	/**
	 * @param string $className
	 * @return bool
	 */
	public function load($className)
	{
		$path = $this->getClassPath($className);

		if ($path === false) {
			\lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
				'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
				'msg' => "Autoload failed. Class '$className' not found",
			]);
			return false;
		}

		require_once($path);
		return true;
	}


	/*******************************************************************************************************************
	 * PRIVATE
	 ******************************************************************************************************************/

	/**
	 * @param string $className
	 * @return string|false
	 */
	private function getSystemClassPath($className)
	{
		$arr = explode('\\', $className);
		if (empty($arr) || count($arr) != 2 || $arr[0] != 'lx') {
			return false;
		}

		$name = $arr[1];
		$map = $this->_systemClassMap;
		if (array_key_exists($name, $map)) {
			$path = $this->phpPath . '/' . $map[$name] . '/' . $name . '.php';
			if (file_exists($path)) {
				return $path;
			}
		}

		return false;
	}

	/**
	 * @param string $className
	 * @return string|false
	 */
	private function getClientClassPath($className)
	{
		$path = $this->sitePath . '/' . str_replace('\\', '/', $className) . '.php';
		if (file_exists($path)) {
			return $path;
		}
		return false;
	}

	/**
	 * @param string $className
	 * @return string|false
	 */
	private function getClassPathWithNamespaceMap($className)
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
				if ($path{-1} != '/') $path .= '/';

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

		return false;
	}

	/**
	 * @param string $className
	 * @return string|false
	 */
	private function getClassPathWithClassMap($className)
	{
		$classes = $this->map->classes;
		if (array_key_exists($className, $classes)) {
			$path = $this->sitePath . '/' . $classes[$className]['path'];
			if (file_exists($path)) {
				return $path;
			}
		}
		return false;
	}
}
