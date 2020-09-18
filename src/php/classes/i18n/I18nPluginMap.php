<?php

namespace lx;

/**
 * Class I18nPluginMap
 * @package lx
 */
class I18nPluginMap extends I18nMap
{
    const DEFAULT_FILE_NAME = 'assets/i18n/main.yaml';

	/** @var array */
	protected $fullMap;

	/**
	 * @return Plugin
	 */
	public function getPlugin()
	{
		return $this->owner;
	}

	/**
	 * @return array
	 */
	public function getFullMap()
	{
		if (!$this->fullMap) {
			$selfMap = $this->getMap();

			$protoService = $this->getPlugin()->getPrototypeService();
			if ($protoService) {
				$serviceMap = $protoService->i18nMap->getFullMap();
				$selfMap = $this->mapMerge($selfMap, $serviceMap);
			}

			$serviceMap = $this->getPlugin()->getService()->i18nMap->getFullMap();
			$selfMap = $this->mapMerge($selfMap, $serviceMap);

			$this->fullMap = $selfMap;
		}

		return $this->fullMap;
	}

	/**
	 * @param string $fileName
	 * @return string
	 */
	protected function getFilePath($fileName)
	{
		return $this->getPlugin()->conductor->getFullPath($fileName);
	}

	/**
	 * @return array|string
	 */
	protected function getFilesFromConfig()
	{
		return $this->getPlugin()->getConfig('i18nFile');
	}
}
