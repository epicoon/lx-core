<?php

namespace lx;

/**
 * Class ApplicationConductor
 * @package lx
 */
class ApplicationConductor extends BaseObject implements ConductorInterface
{
	use ApplicationToolTrait;

	/** @var array */
	private $aliases = [];

	/**
	 * @param string $name
	 * @return string|array|null
	 */
	public function __get($name)
	{
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

	/**
	 * @return string
	 */
	public function getPath()
	{
		return \lx::$conductor->sitePath;
	}

	/**
	 * If the path begins with '/' it will be completed with site path
	 * If the path begins with '@' it will be completed by alias definition
	 * If the path begins with '{package:package-name}' it will be completed relative to package
	 * If the path begins with '{service:service-name}' it will be completed relative to service
	 * If the path begins with '{plugin:plugin-name}' it will be completed relative to plugin
	 * If the path is relative it will be completed with $defaultLocation
	 * If the $defaultLocation is not defined the path will be completed with site path
	 *
	 * @param string $path
	 * @param string $defaultLocation
	 * @return string|false
	 */
	public function getFullPath($path, $defaultLocation = null)
	{
		if ($path{0} == '/') {
			if (preg_match('/^' . str_replace('/', '\/', $this->sitePath) . '/', $path)) {
				return $path;
			}
			return $this->sitePath . $path;
		}

		if ($path{0} == '@') {
			return $this->decodeAlias($path);
		}

		if ($path{0} == '{') {
			return $this->getStuffPath($path);
		}

		if ($defaultLocation === null) $defaultLocation = $this->sitePath;
		if ($defaultLocation{-1} != '/') $defaultLocation .= '/';
		return $defaultLocation . $path;
	}

	/**
	 * @param string $path
	 * @param string $defaultLocation
	 * @return string|false
	 */
	public function getRelativePath($path, $defaultLocation = null)
	{
		$fullPath = $this->getFullPath($path, $defaultLocation);
		if (!$fullPath) {
			return false;
		}

		return explode($this->sitePath . '/', $fullPath)[1];
	}

	/**
	 * @param string $name
	 * @return BaseFile|null
	 */
	public function getFile($name)
	{
		$path = $this->getFullPath($name);
		if (!$path) {
			return null;
		}

		return BaseFile::construct($path);
	}

	/**
	 * @return string
	 */
	public function getSystemPath($name = null)
	{
		return \lx::$conductor->getSystemPath($name);
	}

	/**
	 * @param array $arr
	 */
	public function setAliases($arr)
	{
		$this->aliases = [];
		$this->addAliases($arr);
	}

	/**
	 * @param array $arr
	 */
	public function addAliases($arr)
	{
		foreach ($arr as $name => $path) {
			$this->addAlias($name, $path);
		}
	}

	/**
	 * @param string $name
	 * @param string $path
	 * @param bool $rewrite
	 */
	public function addAlias($name, $path, $rewrite = false)
	{
		if (array_key_exists($name, $this->aliases) && !$rewrite) {
			return;
		}

		$this->aliases[$name] = $path;
	}


	/*******************************************************************************************************************
	 * PRIVATE
	 ******************************************************************************************************************/

	/**
	 * @param string $path
	 * @return string|false
	 */
	private function decodeAlias($path)
	{
		preg_match_all('/^@([_\w][_\-\w\d]*?)(\/|$)/', $path, $arr);
		if (empty($arr[0])) {
			return $path;
		}

		$mask = $arr[0][0];
		$alias = $arr[1][0];

		if (array_key_exists($alias, $this->aliases)) {
			$alias = $this->aliases[$alias];
		} else {
			$alias = $this->$alias;
		}

		if (!$alias) {
			\lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
				'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
				'msg' => "Can't decode alias '$path'",
			]);
			return false;
		}

		$alias .= $arr[2][0];
		$path = str_replace($mask, $alias, $path);

		if ($path{0} == '@') {
			return $this->decodeAlias($path);
		}
		return $path;
	}

	/**
	 * @param string $path
	 * @return string|false
	 */
	private function getStuffPath($path)
	{
		preg_match_all('/^{([^:]+?):([^}]+?)}\/?(.+?)$/', $path, $matches);
		if (empty($matches[1])) {
			\lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
				'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
				'msg' => "Wrong stuff path '$path'",
			]);
			return false;
		}

		$key = $matches[1][0];
		$name = $matches[2][0];
		$relativePath = $matches[3][0];
		if ($key == 'package') {
			$packagePath = $this->app->getPackagePath($name);
			if (!$packagePath) {
				\lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
					'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
					'msg' => "Package '$name' is not found for '$path'",
				]);
				return false;
			}

			return $packagePath . '/' . $relativePath;
		}

		if ($key == 'service') {
			$service = $this->app->getService($name);
			if (!$service) {
				\lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
					'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
					'msg' => "Service '$name' is not found for '$path'",
				]);
				return false;
			}

			return $service->getFilePath($relativePath);
		}

		if ($key == 'plugin') {
			$plugin = $this->app->getPlugin($name);
			if (!$plugin) {
				\lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
					'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
					'msg' => "Plugin '$name' is not found for '$path'",
				]);
				return false;
			}

			return $plugin->getFilePath($relativePath);
		}
	}
}
