<?php

namespace lx;

/**
 * Class I18nApplicationMap
 * @package lx
 */
class I18nApplicationMap extends I18nMap
{
	/** @var array */
	private $used = [];

	/**
	 * @param string $name
	 * @return bool
	 */
	public function inUse($name)
	{
		return array_search($name, $this->used) !== false;
	}

	/**
	 * @param string $name
	 */
	public function noteUse($name)
	{
		$this->used[] = $name;
	}

	/**
	 * @param string $fileName
	 * @return string
	 */
	protected function getFilePath($fileName)
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
