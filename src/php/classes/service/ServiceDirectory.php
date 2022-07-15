<?php

namespace lx;

use lx;

class ServiceDirectory extends Directory
{
    private ?Service $service = null;

	public function __construct(?string $path = null)
	{
		if ($path) {
		    parent::__construct($path);
		}
	}
    
    public function setService(Service $service): void
    {
        $this->service = $service;
        $this->setPath(lx::$app->sitePath . '/' . $service->relativePath);
    }

	public function getService(): ?Service
	{
        return $this->service;
	}

	/**
	 * Any package has to have configuration file
	 * If it doesn't have that file, this directory is not a package
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
}
