<?php

namespace lx;

use lx;

class ServiceI18nMap extends I18nMap
{
	protected ?array $fullMap = null;

	public function getService(): Service
	{
		return $this->owner;
	}

	public function getFullMap(): array
	{
		if (!$this->fullMap) {
			$selfMap = $this->getMap();
			$appMap = lx::$app->i18nMap->getMap();
			$this->fullMap = $this->mapMerge($selfMap, $appMap);
		}

		return $this->fullMap;
	}

	protected function getFilePath(string $fileName): string
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
