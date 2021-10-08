<?php

namespace lx;

use lx;

class SnippetCacheData
{
    use ObjectTrait;

	private SnippetBuildContext $snippetBuildContext;
	private string $buildType;
	private ?Directory $dir = null;
	private ?File $mainFile = null;
	private ?File $mapFile = null;
	private ?array $map = null;

	public function __construct(SnippetBuildContext $snippetBuildContext)
	{
		$this->snippetBuildContext = $snippetBuildContext;
	}

	public function getPlugin(): Plugin
	{
		return $this->snippetBuildContext->getPlugin();
	}

	public function initBuildType(string $buildType): void
	{
		$this->buildType = $buildType;
	}

	public function isEmpty(): bool
	{
		$this->retrieveFiles();
		return !$this->mainFile || !$this->mapFile;
	}

	public function get(): ?array
	{
		if ($this->isEmpty()) {
			return null;
		}

		$map = $this->mapFile->get();
		$map = json_decode($map, true);
		return [
			'rootSnippetKey' => $map['root'],
			'pluginModif' => $map['pluginModif'] ?? [],
			'dependencies' => $map['dependencies'],
			'cache' => $this->mainFile->get(),
		];
	}

	public function renew(string $rootKey, array $snippets, array $snippetsData, string $commonData): void
	{
		$plugin = $this->getPlugin();
		$snippetBundlesDir = new Directory($plugin->conductor->getSnippetsCachePath());
		$snippetBundlesDir->remove();
		$snippetBundlesDir->make();

		$mainFile = $snippetBundlesDir->makeFile('__main__.json');
		$mainFile->put($commonData);

		$map = [
			'root' => $rootKey,
			'map' => [],
		];
		$pluginModif = $snippets[$rootKey]->getPluginModifications();
		if (!empty($pluginModif)) {
			$map['pluginModif'] = $pluginModif;
		}

		$commonDependencies = new JsCompileDependencies();
		/** @var $snippet Snippet */
		foreach ($snippets as $key => $snippet) {
			$cacheFileName = $key . '.json';
			$cacheFile = $snippetBundlesDir->makeFile($cacheFileName);
			$cacheFile->put($snippetsData[$key]);

			$path = $snippet->getFile()->getRelativePath($plugin->directory);
			if (!$path) {
				$path = '@site/' . $snippet->getFile()->getRelativePath(lx::$app->sitePath);
			}

			$dependencies = $snippet->getDependencies();
			$this->processDependencies($commonDependencies, $dependencies);
			$data = [
				'key' => $key,
				'path' => $path,
				'cache' => $cacheFileName,

				'dependencies' => $dependencies,
				'files' => $snippet->getFileDependencies(),
				'snippets' => $snippet->getInnerSnippetKeys(),
			];
			if (!empty($snippet->getAttributes())) {
				$data['attributes'] = $snippet->getAttributes();
			}
			$map['map'][$key] = $data;
		}

		$map['dependencies'] = $commonDependencies->toArray();
		$mapFile = $snippetBundlesDir->makeFile('__map__.json');
		$mapFile->put($map);
	}

	public function smartRenew(array $changed, array $snippets): array
	{
		$plugin = $this->getPlugin();
		$cachePath = $plugin->conductor->getSnippetsCachePath();
		$map = $this->getMap();

		if (array_key_exists($map['root'], $snippets)) {
			$pluginModif = $snippets[$map['root']]->getPluginModifications();
			if (empty($pluginModif)) {
				unset($map['pluginModif']);
			} else {
				$map['pluginModif'] = $pluginModif;
			}
		}

		$allChanged = [];
		$rec = function($arr) use ($map, &$allChanged, &$rec) {
			foreach ($arr as $key) {
				$allChanged[] = $key;
				$rec($map['map'][$key]['snippets']);
			}
		};
		$rec($changed);

		foreach ($allChanged as $key) {
			$cacheFile = new File($plugin->getFilePath($cachePath . '/' . $key . '.json'));
			$cacheFile->remove();
			unset($map['map'][$key]);
		}

		$oldDependencies = $map['dependencies'];
		$commonDependencies = new JsCompileDependencies();
		$mainArr = [];
		foreach ($map['map'] as $key => $data) {
			$cacheFile = $plugin->getFile($cachePath . '/' . $data['cache']);
			$mainArr[$key] = json_decode($cacheFile->get(), true);
			$restoredDependencies = $this->restoreDependencies($data['dependencies'], $oldDependencies);
			$commonDependencies->add($restoredDependencies);
		}

		/** @var $snippet Snippet */
		foreach ($snippets as $key => $snippet) {
			$mainArr[$key] = $snippet->getData();
		}

		/** @var $snippet Snippet */
		foreach ($snippets as $key => $snippet) {
			$cacheFileName = $key . '.json';
			$cacheFile = new File($plugin->getFilePath($cachePath . '/' . $cacheFileName));
			$cacheFile->put($mainArr[$key]);

			$path = $snippet->getFile()->getRelativePath($plugin->directory);
			if (!$path) {
				$path = '@site/' . $snippet->getFile()->getRelativePath(lx::$app->sitePath);
			}

			$dependencies = $snippet->getDependencies();
			$this->processDependencies($commonDependencies, $dependencies);
			$data = [
				'key' => $key,
				'path' => $path,
				'cache' => $cacheFileName,

				'dependencies' => $dependencies,
				'files' => $snippet->getFileDependencies(),
				'snippets' => $snippet->getInnerSnippetKeys(),
			];
			if (!empty($snippet->getAttributes())) {
				$data['attributes'] = $snippet->getAttributes();
			}
			$map['map'][$key] = $data;
		}

		$main = json_encode($mainArr);
		$map['dependencies'] = $commonDependencies->toArray();
		$this->mapFile->put($map);
		$this->mainFile->put($main);

		return [
			'rootSnippetKey' => $map['root'],
			'pluginModif' => $map['pluginModif'] ?? [],
			'dependencies' => $commonDependencies,
			'cache' => $main,
		];
	}

	public function getDiffs(): array
	{
		$plugin = $this->getPlugin();
		$cachePath = $plugin->conductor->getSnippetsCachePath();
		$map = $this->getMap();

		$changes = [];
		foreach ($map['map'] as $key => $value) {
			$workFile = $plugin->getFile($value['path']);
			if (!$workFile || !$workFile->exists()) {
				$changes[] = $key;
				continue;
			}

			$cacheFile = $plugin->getFile($cachePath . '/' . $value['cache']);
			if (!$cacheFile || !$cacheFile->exists()) {
				$changes[] = $key;
				continue;
			}

			if ($this->checkCacheIsOutdated($cacheFile, array_merge([$workFile->getPath()], $value['files']))) {
				$changes[] = $key;
			}
		}

		return $changes;
	}

	public function getMap(): array
	{
		if ($this->map === null) {
			$this->retrieveFiles();
			if ($this->isEmpty()) {
				$this->map = [];
			} else {
				$this->map = json_decode($this->mapFile->get(), true);
			}
		}

		return $this->map;
	}


	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * PRIVATE
	 * * * * * * * * * * * * * * * * * * * * * * * * * * ** * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	private function checkCacheIsOutdated(File $cacheFile, array $fileNames): bool
    {
		foreach ($fileNames as $name) {
			$file = new File($name);
			if ($file->isNewer($cacheFile)) {
				return true;
			}
		}

		return false;
	}

	private function processDependencies(JsCompileDependencies $commonDependencies, array &$dependencies): void
	{
		$commonDependencies->add($dependencies);
		if (!isset($dependencies['plugins'])) {
			return;
		}

		foreach ($dependencies['plugins'] as &$dependency) {
			$dependency = $dependency['anchor'];
		}
		unset($dependency);
	}

	private function restoreDependencies(array $dependencies, array $oldDependencies): array
	{
		if (!isset($oldDependencies['plugins']) || !isset($dependencies['plugins'])) {
			return $dependencies;
		}
		
		$map = ArrayHelper::map($oldDependencies['plugins'], 'anchor');
		foreach ($dependencies['plugins'] as &$dependency) {
			$dependency = $map[$dependency];
		}
		unset($dependency);

		return $dependencies;
	}

	/**
	 * Retrieve main file to the field [[$this->mainFile]] and map file fo the field [[$this->mapFile]]
	 */
	private function retrieveFiles(): void
	{
		if ($this->dir !== null) {
			return;
		}

		$plugin = $this->getPlugin();
		$path = $plugin->conductor->getSnippetsCachePath();
		$dir = new Directory($path);
		if (!$dir->exists()) {
			$this->dir = null;
			return;
		}

		$this->dir = $dir;
		$this->mainFile = $dir->get('__main__.json');
		$this->mapFile = $dir->get('__map__.json');
	}
}
