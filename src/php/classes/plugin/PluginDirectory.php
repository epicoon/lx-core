<?php

namespace lx;

use lx;

class PluginDirectory extends Directory implements FusionComponentInterface
{
	use FusionComponentTrait;

    /**
     * @param array|string $config
     */
	public function __construct($config = [])
	{
		if (is_string($config)) {
			$config = ['path' => $config];
		}

		$this->__objectConstruct($config);

		$path = $this->getPlugin()
			? lx::$app->sitePath . '/' . $this->getPlugin()->relativePath
			: ($config['path'] ?? null);

		if ($path) {
		    parent::__construct($path);
		} else {
			\lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
				'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
				'msg' => "Path for PluginDirectory is undefined",
			]);
		}
	}

	public function getPlugin(): ?Plugin
	{
		return $this->owner;
	}

	public function getConfigFile(): ?DataFileInterface
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
