<?php

namespace lx;

class PluginI18nMap extends I18nMap
{
    const DEFAULT_FILE_NAME = 'assets/i18n/main.yaml';

	protected ?array $fullMap = null;

	public function getPlugin(): Plugin
	{
		return $this->owner;
	}

	public function getFullMap(): array
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

	protected function getFilePath(string $fileName): string
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
