<?php

namespace lx;

use lx;

abstract class I18nMap implements FusionComponentInterface
{
	use FusionComponentTrait;

	const DEFAULT_FILE_NAME = 'i18n';

	protected ?array $map = null;
	protected ?array $tags = null;

	public function getMap(): array
	{
		if ($this->map === null) {
			$this->loadMap();
		}

		return $this->map ?? [];
	}

	public function getFullMap(): array
	{
		return $this->getMap();
	}

	public function add(iterable $map, bool $rewrite = false): void
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
	 */
	protected function files(): array
	{
		return [];
	}

	abstract protected function getFilePath(string $fileName): string;

	/**
	 * @return string|array
	 */
	abstract protected function getFilesFromConfig();

	protected function mapMerge(array $map1, array $map2, bool $rewrite = false): array
	{
		$codes = lx::$app->language->codes;

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
	private function loadMap(): void
	{
		if ($this->map !== null) {
			return;
		}

		$mapFromMethod = $this->getMapFromMethodFiles();
		$mapFromConfig = $this->getMapFromConfigFiles();

		$this->map = $this->mapMerge($mapFromConfig, $mapFromMethod);
	}

	private function getMapFromMethodFiles(): array
	{
		$fileNames = $this->files();
		$result = [];
		foreach ($fileNames as $fileName) {
			$result = $this->mapMerge($result, $this->loadFromFile($fileName), true);
		}

		return $result;
	}

	private function getMapFromConfigFiles(): array
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

	private function loadFromFile(string $fileName): array
	{
		$path = $this->getFilePath($fileName);

		if ($fileName[-1] == '/') {
			$result = [];
			$dir = new Directory($path);
			$ff = $dir->getFileNames('*.*');
			foreach ($ff as $f) {
				$fPath = "$path$f";
				/** @var DataFileInterface $file */
				$file = lx::$app->diProcessor->createByInterface(DataFileInterface::class, [$fPath]);
				if ($file->exists()) {
					$result = $this->mapMerge($result, $file->get(), true);
				}
			}
		} else {
			/** @var DataFileInterface $file */
			$file = lx::$app->diProcessor->createByInterface(DataFileInterface::class, [$path]);
			return $file->exists() ? $file->get() : [];
		}
	}
}
