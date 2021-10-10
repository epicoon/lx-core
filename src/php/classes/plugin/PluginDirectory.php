<?php

namespace lx;

use lx;

class PluginDirectory extends Directory
{
    private ?Plugin $plugin = null;

    /**
     * @param array|string $config
     */
	public function __construct(?string $path = null)
	{
        if ($path) {
            parent::__construct($path);
        }
	}
    
    public function setPlugin(Plugin $plugin): void
    {
        $this->plugin = $plugin;
        $this->setPath(lx::$app->sitePath . '/' . $plugin->relativePath);
    }

	public function getPlugin(): ?Plugin
	{
        return $this->plugin;
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
