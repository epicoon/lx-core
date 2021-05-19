<?php

namespace lx;

class ApplicationConductor implements ConductorInterface
{
    use ObjectTrait;
	use ApplicationToolTrait;

	private array $aliases = [];

	/**
	 * @return string|array|null
	 */
	public function __get(string $name)
	{
		$result = $this->__objectGet($name);
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

	public function getPath(): string
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
	 */
	public function getFullPath(string $path, ?string $defaultLocation = null): ?string
	{
		if ($path[0] == '/') {
			if (preg_match('/^' . str_replace('/', '\/', $this->sitePath) . '/', $path)) {
				return $path;
			}
			return $this->sitePath . $path;
		}

		if ($path[0] == '@') {
			return $this->decodeAlias($path);
		}

		if ($path[0] == '{') {
			return $this->getStuffPath($path);
		}

		if ($defaultLocation === null) $defaultLocation = $this->sitePath;
		if ($defaultLocation[-1] != '/') $defaultLocation .= '/';
		return $defaultLocation . $path;
	}

	public function getRelativePath(string $path, ?string $defaultLocation = null): string
	{
		$fullPath = $this->getFullPath($path, $defaultLocation);
		if (!$fullPath) {
			return false;
		}

		return explode($this->sitePath . '/', $fullPath)[1];
	}

	public function getFile(string $name): ?BaseFile
	{
		$path = $this->getFullPath($name);
		if (!$path) {
			return null;
		}

		return BaseFile::construct($path);
	}

	public function getSystemPath(?string $name = null)
	{
		return \lx::$conductor->getSystemPath($name);
	}

	public function setAliases(array $arr)
	{
		$this->aliases = [];
		$this->addAliases($arr);
	}

	public function addAliases(array $arr)
	{
		foreach ($arr as $name => $path) {
			$this->addAlias($name, $path);
		}
	}

	public function addAlias(string $name, string $path, bool $rewrite = false): void
	{
		if (array_key_exists($name, $this->aliases) && !$rewrite) {
			return;
		}

		$this->aliases[$name] = $path;
	}


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * PRIVATE
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	private function decodeAlias(string $path): ?string
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
			return null;
		}

		$alias .= $arr[2][0];
		$path = str_replace($mask, $alias, $path);

		if ($path[0] == '@') {
			return $this->decodeAlias($path);
		}
		return $path;
	}

	private function getStuffPath(string $path): ?string
	{
		preg_match_all('/^{([^:]+?):([^}]+?)}\/?(.+?)$/', $path, $matches);
		if (empty($matches[1])) {
			\lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
				'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
				'msg' => "Wrong stuff path '$path'",
			]);
			return null;
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
				return null;
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
				return null;
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
				return null;
			}

			return $plugin->getFilePath($relativePath);
		}
	}
}
