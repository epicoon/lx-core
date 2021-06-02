<?php

namespace lx;

/**
 * Class ServiceI18nMap
 * @package lx
 */
class ServiceI18nMap extends I18nMap
{
	/** @var array */
	protected $fullMap;

	/**
	 * @return Service
	 */
	public function getService()
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
			$appMap = $this->app->i18nMap->getMap();
			$this->fullMap = $this->mapMerge($selfMap, $appMap);
		}

		return $this->fullMap;
	}

	/**
	 * @param string $fileName
	 * @return string
	 */
	protected function getFilePath($fileName)
	{
		return $this->getService()->conductor->getFullPath($fileName);
	}

	/**
	 * @return array|string
	 */
	protected function getFilesFromConfig()
	{
		return $this->getService()->getConfig('i18nFile');
	}
}
