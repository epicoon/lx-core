<?php

namespace lx;

class PackageDirectory extends Directory implements FusionComponentInterface
{
	use FusionComponentTrait;

    /**
     * @param string|array $config
     */
	public function __construct($config)
	{
	    if (is_string($config)) {
            $config = ['path' => $config];
        }

		$this->__objectConstruct($config);

		$path = $this->getService()
			? $this->getService()->app->sitePath . '/' . $this->getService()->relativePath
			: ($config['path'] ?? null);

		if ($path) {
		    parent::__construct($path);
		} else {
			\lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
				'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
				'msg' => "Path for PackageDirectory is undefined",
			]);
		}
	}

	public function getService(): ?Service
	{
		return $this->owner;
	}

	/**
	 * Any package has to have configuration file
	 * If it doesn't have that file, this directory is not package
	 */
	public function getConfigFile(): ?DataFileInterface
	{
		$configPathes = \lx::$conductor->getPackageConfigNames();
		$path = $this->getPath();
		foreach ($configPathes as $configPath) {
			$fullPath = $path . '/' . $configPath;
			/** @var DataFileInterface $file */
			$file = \lx::$app->diProcessor
                ? \lx::$app->diProcessor->createByInterface(DataFileInterface::class, [$fullPath])
                : new DataFile($fullPath);
			if ($file->exists()) {
				return $file;
			}
		}
		return null;
	}

	/**
	 * Method checks directory is lx-package (it has to have special lx-configuration file)
	 */
	public function isLx(): bool
	{
		$lxConfigPathes = \lx::$conductor->getLxPackageConfigNames();
		$path = $this->getPath();
		foreach ($lxConfigPathes as $configPath) {
			$fullPath = $path . '/' . $configPath;
			/** @var DataFileInterface $file */
			$file = \lx::$app->diProcessor->createByInterface(DataFileInterface::class, [$fullPath]);
			if ($file->exists()) {
				return true;
			}
		}
		return false;
	}
}
