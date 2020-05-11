<?php

namespace lx;

/**
 * Class SnippetCacheData
 * @package lx
 */
class SnippetCacheData extends BaseObject
{
	use ApplicationToolTrait;

	/** @var SnippetBuildContext */
	private $snippetBuildContext;

	/** @var string */
	private $buildType;

	/** @var Directory */
	private $dir;

	/** @var File */
	private $mainFile;

	/** @var File */
	private $mapFile;

	/** @var array */
	private $map;

	/**
	 * SnippetCacheData constructor.
	 * @param SnippetBuildContext $snippetBuildContext
	 */
	public function __construct($snippetBuildContext)
	{
		$this->snippetBuildContext = $snippetBuildContext;
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
	 * @param string $buildType
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
		return !$this->mainFile || !$this->mapFile;
	}

	/**
	 * @return array|false
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
			'pluginModif' => $map['pluginModif'] ?? [],
			'dependencies' => new JsCompileDependencies($map['dependencies']),
			'cache' => $this->mainFile->get(),
		];
	}

	/**
	 * @param string $rootKey
	 * @param array $snippets
	 * @param array $snippetsData
	 * @param array $commonData
	 */
	public function renew($rootKey, $snippets, $snippetsData, $commonData)
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
				$path = '@site/' . $snippet->getFile()->getRelativePath($this->app->sitePath);
			}

			$dependencies = $snippet->getDependencies()->toArray();
			$this->processDependencies($commonDependencies, $dependencies);
			$data = [
				'key' => $key,
				'path' => $path,
				'cache' => $cacheFileName,

				'dependencies' => $dependencies,
				'files' => $snippet->getFileDependencies(),
				'snippets' => $snippet->getInnerSnippetKeys(),
			];
			if (!empty($snippet->getRenderParams())) {
				$data['renderParams'] = $snippet->getRenderParams();
			}
			if (!empty($snippet->getClientParams())) {
				$data['clientParams'] = $snippet->getClientParams();
			}
			$map['map'][$key] = $data;
		}

		$map['dependencies'] = $commonDependencies->toArray();
		$mapFile = $snippetBundlesDir->makeFile('__map__.json');
		$mapFile->put($map);
	}

	/**
	 * @param array $changed
	 * @param array $snippets
	 * @return array
	 */
	public function smartRenew($changed, $snippets)
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
				$path = '@site/' . $snippet->getFile()->getRelativePath($this->app->sitePath);
			}

			$dependencies = $snippet->getDependencies()->toArray();
			$this->processDependencies($commonDependencies, $dependencies);
			$data = [
				'key' => $key,
				'path' => $path,
				'cache' => $cacheFileName,

				'dependencies' => $dependencies,
				'files' => $snippet->getFileDependencies(),
				'snippets' => $snippet->getInnerSnippetKeys(),
			];
			if (!empty($snippet->getRenderParams())) {
				$data['renderParams'] = $snippet->getRenderParams();
			}
			if (!empty($snippet->getClientParams())) {
				$data['clientParams'] = $snippet->getClientParams();
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

	/**
	 * @return array
	 */
	public function getDiffs()
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

	/**
	 * @return array
	 */
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


	/*******************************************************************************************************************
	 * PRIVATE
	 ******************************************************************************************************************/

	/**
	 * @param File $cacheFile
	 * @param array $fileNames
	 * @return bool
	 */
	private function checkCacheIsOutdated($cacheFile, $fileNames) {
		foreach ($fileNames as $name) {
			$file = new File($name);
			if ($file->isNewer($cacheFile)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param array $commonDependencies
	 * @param array $dependencies
	 */
	private function processDependencies($commonDependencies, &$dependencies)
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

	/**
	 * @param array $dependencies
	 * @param array $oldDependencies
	 * @return array
	 */
	private function restoreDependencies($dependencies, $oldDependencies)
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
	 * Retrieve main file to the field [[$this->mainFile]] and map file fo the field [[$this->>mapFile]]
	 */
	private function retrieveFiles()
	{
		if ($this->dir !== null) {
			return;
		}

		$plugin = $this->getPlugin();
		$path = $plugin->conductor->getSnippetsCachePath();
		$dir = new Directory($path);
		if (!$dir->exists()) {
			$this->dir = false;
			return;
		}

		$this->dir = $dir;
		$this->mainFile = $dir->get('__main__.json');
		$this->mapFile = $dir->get('__map__.json');
	}
}
