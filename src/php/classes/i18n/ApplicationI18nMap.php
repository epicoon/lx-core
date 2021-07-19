<?php

namespace lx;

class ApplicationI18nMap extends I18nMap
{
	private array $used = [];

	public function inUse(string $name): bool
	{
		return array_search($name, $this->used) !== false;
	}

	public function noteUse(string $name): void
	{
		$this->used[] = $name;
	}

	protected function getFilePath(string $fileName): string
	{
		return $this->app->conductor->getFullPath($fileName);
	}

	/**
	 * @return array|string
	 */
	protected function getFilesFromConfig()
	{
		return $this->app->getConfig('i18nFile');
	}
}
