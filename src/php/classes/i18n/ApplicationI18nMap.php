<?php

namespace lx;

use lx;

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
		return lx::$app->conductor->getFullPath($fileName);
	}

	/**
	 * @return array|string
	 */
	protected function getFilesFromConfig()
	{
		return lx::$app->getConfig('i18nFile');
	}
}
