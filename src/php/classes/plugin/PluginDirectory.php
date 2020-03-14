<?php

namespace lx;

/**
 * Class PluginDirectory
 * @package lx
 */
class PluginDirectory extends BaseObject implements FusionComponentInterface
{
	use FusionComponentTrait;

	/** @var Directory */
	protected $dir;

	/**
	 * PackageDirectory constructor.
	 * @param array $config
	 */
	public function __construct($config = [])
	{
		if (is_string($config)) {
			$config = ['path' => $config];
		}

		parent::__construct($config);

		$path = $this->getPlugin()
			? $this->getPlugin()->app->sitePath . '/' . $this->getPlugin()->relativePath
			: ($config['path'] ?? null);

		if ($path) {
			$this->dir = new Directory($path);
			$this->delegateMethodsCall('dir');
		} else {
			\lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
				'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
				'msg' => "Path for PluginDirectory is undefined",
			]);
		}
	}

	/**
	 * @return Plugin
	 */
	public function getPlugin()
	{
		return $this->owner;
	}

	/**
	 * @return DataFileInterface|null
	 */
	public function getConfigFile()
	{
		$configPathes = \lx::$conductor->getPackageConfigNames();
		$path = $this->getPath();
		foreach ($configPathes as $configPath) {
			$fullPath = $path . '/' . $configPath;
			/** @var DataFileInterface $file */
			$file = \lx::$app->diProcessor->createByInterface(DataFileInterface::class, [$fullPath]);
			if ($file->exists()) {
				return $file;
			}
		}
		return null;
	}
}
