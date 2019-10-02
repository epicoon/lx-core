<?php

namespace lx;

/**
 * Class SnippetCacheData
 * @package lx
 */
class SnippetCacheData extends ApplicationTool
{
	private $snippetBuildContext;
	private $buildType;
	private $dir;
	private $mainFile;
	private $mapFile;
	private $map;

	/**
	 * SnippetCacheData constructor.
	 * @param $snippetBuildContext SnippetBuildContext
	 */
	public function __construct($snippetBuildContext)
	{
		parent::__construct($snippetBuildContext->app);

		$this->snippetBuildContext = $snippetBuildContext;
		$this->mainFile = false;
		$this->mapFile = false;
		$this->map = null;
	}

	/**
	 * @return Plugin
	 */
	public function getPlugin()
	{
		return $this->snippetBuildContext->getPlugin();
	}

	/**
	 * @param $buildType int
	 */
	public function initBuildType($buildType)
	{
		$this->buildType = $buildType;
	}

	/**
	 * @return bool
	 */
	public function isEmpty()
	{
		$this->retrieveFiles();
		return $this->mainFile === false || $this->mapFile === false;
	}

	/**
	 * @return array|bool
	 */
	public function get()
	{
		if ($this->isEmpty()) {
			return false;
		}

		$map = $this->mapFile->get();
		$map = json_decode($map, true);
		return [
			'rootSnippetKey' => $map['root'],
			'dependencies' => new JsCompileDependencies($map['dependencies']),
			'cache' => $this->mainFile->get(),
		];
	}

	public function renew($rootKey, $snippets, $snippetsData, $commonData)
	{
		$plugin = $this->getPlugin();
		$bundlesPath = $plugin->getFilePath($plugin->getConfig('bundles'));
		$snippetBundlesDir = new Directory($bundlesPath . '/snippets');
		$snippetBundlesDir->remove();
		$snippetBundlesDir->make();

		$mainFile = $snippetBundlesDir->makeFile('__main__.json');
		$mainFile->put($commonData);

		$map = [
			'root' => $rootKey,
			'map' => [],
		];
		$dependencies = new JsCompileDependencies();
		/** @var $snippet Snippet */
		foreach ($snippets as $key => $snippet) {
			$cacheFileName = $key . '.json';
			$cacheFile = $snippetBundlesDir->makeFile($cacheFileName);
			$cacheFile->put($snippetsData[$key]);

			$path = $snippet->getFile()->getRelativePath($plugin->directory);
			if (!$path) {
				$path = '@site/' . $snippet->getFile()->getRelativePath($this->app->sitePath);
			}

			$data = [
				'key' => $key,
				'path' => $path,
				'cache' => $cacheFileName,

				'dependencies' => $snippet->getDependencies()->toArray(),
				'files' => $snippet->getFileDependencies(),
				'snippets' => $snippet->innerSnippetKeys,
			];
			if (!empty($snippet->renderParams)) $data['renderParams'] = $snippet->renderParams;
			if (!empty($snippet->clientParams)) $data['clientParams'] = $snippet->clientParams;
			$map['map'][$key] = $data;
			$dependencies->add($snippet->getDependencies());
		}

		$map['dependencies'] = $dependencies->toArray();
		$mapFile = $snippetBundlesDir->makeFile('__map__.json');
		$mapFile->put($map);
	}

	public function smartRenew($changed, $snippets)
	{
		$plugin = $this->getPlugin();
		$cachePath = $plugin->getFilePath($plugin->getConfig('bundles')) . '/snippets';
		$map = $this->getMap();

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

		$dependencies = new JsCompileDependencies();
		$mainArr = [];
		foreach ($map['map'] as $key => $data) {
			$cacheFile = $plugin->getFile($cachePath . '/' . $data['cache']);
			$mainArr[$key] = json_decode($cacheFile->get(), true);
			$dependencies->add($data['dependencies']);
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
				$path = '@site/' . $snippet->getFile()->getRelativePath($this->app->sitePath);
			}

			$data = [
				'key' => $key,
				'path' => $path,
				'cache' => $cacheFileName,

				'dependencies' => $snippet->getDependencies()->toArray(),
				'files' => $snippet->getFileDependencies(),
				'snippets' => $snippet->innerSnippetKeys,
			];
			if (!empty($snippet->renderParams)) $data['renderParams'] = $snippet->renderParams;
			if (!empty($snippet->clientParams)) $data['clientParams'] = $snippet->clientParams;
			$map['map'][$key] = $data;
			$dependencies->add($snippet->getDependencies());
		}

		$main = json_encode($mainArr);
		$map['dependencies'] = $dependencies->toArray();
		$this->mapFile->put($map);
		$this->mainFile->put($main);

		return [
			'rootSnippetKey' => $map['root'],
			'dependencies' => $dependencies,
			'cache' => $main,
		];
	}

	public function getDiffs()
	{
		$plugin = $this->getPlugin();
		$cachePath = $plugin->getFilePath($plugin->getConfig('bundles')) . '/snippets';
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

	public function getMap()
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

	private function checkCacheIsOutdated($cacheFile, $fileNames) {
		foreach ($fileNames as $name) {
			$file = new File($name);
			if ($file->isNewer($cacheFile)) {
				return true;
			}
		}

		return false;
	}

	private function retrieveFiles()
	{
		if ($this->dir !== null) {
			return;
		}

		$plugin = $this->getPlugin();
		$bundlesPath = $plugin->getFilePath($plugin->getConfig('bundles'));
		$dir = new Directory($bundlesPath . '/snippets');
		if (!$dir->exists()) {
			$this->dir = false;
			return;
		}

		$this->dir = $dir;
		$this->mainFile = $dir->get('__main__.json');
		$this->mapFile = $dir->get('__map__.json');
	}
}
