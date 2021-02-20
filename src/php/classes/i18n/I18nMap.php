<?php

namespace lx;

/**
 * Class I18nMap
 * @package lx
 */
abstract class I18nMap implements FusionComponentInterface
{
    use ObjectTrait;
	use ApplicationToolTrait;
	use FusionComponentTrait;

	const DEFAULT_FILE_NAME = 'i18n';

	/** @var array */
	protected $map = null;
	
	/** @var array */
	protected $tags;

	/**
	 * @return array
	 */
	public function getMap()
	{
		if ($this->map === null) {
			$this->loadMap();
		}

		return $this->map;
	}

	/**
	 * @return array
	 */
	public function getFullMap()
	{
		return $this->getMap();
	}

	/**
	 * @param iterable $map
	 * @param bool $rewrite
	 */
	public function add($map, $rewrite = false)
	{
		if (!is_array($map) && is_object($map) && method_exists($map, 'toArray')) {
			$map = $map->toArray();
		}

		if (!is_array($map)) {
			return;
		}

		$this->loadMap();
		$this->map = $this->mapMerge($this->map, $map, $rewrite);
	}

	/**
	 * This method helps to use files with code (not with configuration)
	 *
	 * @return array
	 */
	protected function files()
	{
		return [];
	}

	/**
	 * @param string $fileName
	 * @return string
	 */
	abstract protected function getFilePath($fileName);

	/**
	 * @return string|array
	 */
	abstract protected function getFilesFromConfig();

	/**
	 * @param array $map1
	 * @param array $map2
	 * @param bool $rewrite
	 * @return array
	 */
	protected function mapMerge($map1, $map2, $rewrite = false)
	{
		$codes = $this->app->language->codes;

		foreach ($map2 as $key => $value) {
			if (!in_array($key, $codes)) {
				if (is_array($this->tags) && array_key_exists($key, $this->tags)) {
					$key = $this->tags[$key];
				}
			}

			if (!in_array($key, $codes)) {
				continue;
			}

			if (!array_key_exists($key, $map1)) {
				$map1[$key] = $value;
			} else {
				if ($rewrite) {
					$map1[$key] = $value + $map1[$key];
				} else {
					$map1[$key] += $value;
				}
			}
		}

		return $map1;
	}

	/**
	 * Method loads all files with translations
	 */
	private function loadMap()
	{
		if ($this->map !== null) {
			return;
		}

		$mapFromMethod = $this->getMapFromMethodFiles();
		$mapFromConfig = $this->getMapFromConfigFiles();

		$this->map = $this->mapMerge($mapFromConfig, $mapFromMethod);
	}

	/**
	 * @return array
	 */
	private function getMapFromMethodFiles()
	{
		$fileNames = $this->files();
		$result = [];
		foreach ($fileNames as $fileName) {
			$result = $this->mapMerge($result, $this->loadFromFile($fileName), true);
		}

		return $result;
	}

	/**
	 * @return array
	 */
	private function getMapFromConfigFiles()
	{
        $filesFromConfig = $this->getFilesFromConfig();

        if ( ! $filesFromConfig) {
			$filesFromConfig = static::DEFAULT_FILE_NAME;
		}

		if ( ! is_array($filesFromConfig)) {
			$filesFromConfig = [$filesFromConfig];
		}

		$result = [];
		foreach ($filesFromConfig as $fileName) {
			$result = $this->mapMerge($result, $this->loadFromFile($fileName), true);
		}

		return $result;
	}

	/**
	 * @param string $fileName
	 * @return array
	 */
	private function loadFromFile($fileName)
	{
		$path = $this->getFilePath($fileName);

		if ($fileName[-1] == '/') {
			$result = [];
			$dir = new Directory($path);
			$ff = $dir->getFiles('*.*', Directory::FIND_NAME);
			foreach ($ff as $f) {
				$fPath = "$path$f";
				/** @var DataFileInterface $file */
				$file = $this->app->diProcessor->createByInterface(DataFileInterface::class, [$fPath]);
				if ($file->exists()) {
					$result = $this->mapMerge($result, $file->get(), true);
				}
			}
		} else {
			/** @var DataFileInterface $file */
			$file = $this->app->diProcessor->createByInterface(DataFileInterface::class, [$path]);
			return $file->exists() ? $file->get() : [];
		}
	}
}
