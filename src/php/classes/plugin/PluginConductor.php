<?php

namespace lx;

use lx;

/**
 * Class PluginConductor
 * @package lx
 */
class PluginConductor implements ConductorInterface, FusionComponentInterface
{
    use ObjectTrait;
	use ApplicationToolTrait;
	use FusionComponentTrait;

	/** @var array */
	private $imageMap;

	/** @var array */
	private $cssMap;

	/**
	 * @return Plugin
	 */
	public function getPlugin()
	{
		return $this->owner;
	}

	/**
	 * @return string
	 */
	public function getPath()
	{
		return $this->getPlugin()->directory->getPath();
	}

	/**
	 * @param string $fileName
	 * @param string $relativePath
	 * @return string
	 */
	public function getFullPath($fileName, $relativePath = null)
	{
		if ($fileName[0] == '@') {
			$fileName = $this->decodeAlias($fileName);
		}

		if ($relativePath === null) {
			$relativePath = $this->getPath();
		}

		return $this->app->conductor->getFullPath($fileName, $relativePath);
	}

	/**
	 * @param string $path
	 * @param string $defaultLocation
	 * @return string
	 */
	public function getRelativePath($path, $defaultLocation = null)
	{
		$fullPath = $this->getFullPath($path, $defaultLocation);
		return explode($this->getPath() . '/', $fullPath)[1];
	}

	/**
	 * @param string $name
	 * @return BaseFile|null
	 */
	public function getFile($name)
	{
		$path = $this->getFullPath($name);
		if (!$path) {
			return null;
		}

		return BaseFile::construct($path);
	}

	/**
	 * @return string
	 */
	public function getImagePath($fileName)
	{
		$map = $this->getImagePathesInSite();
		if ($fileName[0] == '@') {
			preg_match('/^@([^\/]+?)(\/.+)$/', $fileName, $match);
			if (empty($match)) {
				return '';
			}
			$key = $match[1];
			if (!array_key_exists($key, $map)) {
				return '';
			}
			return lx::$conductor->sitePath . $map[$key] . $match[2];
		} else {
			if (array_key_exists('default', $map)) {
				return lx::$conductor->sitePath . $map['default'] . '/' . $fileName;
			}

			return $this->getFullPath($fileName);
		}
	}

	/**
	 * @return string
	 */
	public function getSystemPath($name = null)
	{
        $path = $this->getPath() . '/.system';
        if ($name) {
            return $path . '/' . $name;
        }

        return $path;
	}

    /**
     * @return string
     */
    public function getLocalSystemPath($name = null)
    {
        $path = lx::$conductor->getSystemPath('services') . '/'
            . lx::$app->conductor->getRelativePath($this->getPath(), lx::$app->sitePath);

        return $path;
    }

	/**
	 * @return string
	 */
	public function getSnippetsCachePath()
	{
		return $this->getLocalSystemPath() . '/snippet_cache';
	}

	/**
	 * @param string $path
	 * @return bool
	 */
	public function pluginContains($path)
	{
		return $this->getPlugin()->directory->contains($path);
	}

	/**
	 * If $name isn't defined path to the root snippet will be returned
	 *
	 * @param string $name
	 * @return string|null
	 */
	public function getSnippetPath($name = null)
	{
		if ($name === null) {
			return $this->getFullPath($this->getPlugin()->getConfig('rootSnippet'));
		}

		$snippetDirs = $this->getPlugin()->getConfig('snippets');
		if (!$snippetDirs) {
			return null;
		}

		foreach ((array)$snippetDirs as $snippetDir) {
			$path = $this->getFullPath($snippetDir . '/' . $name);
			$fullPath = Snippet::defineSnippetPath($path);
			if ($fullPath) {
				return $fullPath;
			}
		}

		return null;
	}

    /**
     * @return Directory[]
     */
	public function getSnippetDirectories()
    {
        $snippetDirs = $this->getPlugin()->getConfig('snippets');
        if (!$snippetDirs) {
            return [];
        }

        $result = [];
        foreach ((array)$snippetDirs as $snippetDir) {
            $dir = $this->getFile($snippetDir);
            if ($dir && $dir->isDir()) {
                $result[] = $dir;
            }
        }
        return $result;
    }

	/**
	 * @param string $name
	 * @return Respondent|null
	 */
	public function getRespondent($name)
	{
		$plugin = $this->getPlugin();

		$respondents = (array)$plugin->getConfig('respondents');
		if (!array_key_exists($name, $respondents)) {
			return null;
		}

		$respondentClassName = $respondents[$name];
		if (!ClassHelper::exists($respondentClassName)) {
			$respondentClassName = ClassHelper::getNamespace($plugin) . '\\' . $respondentClassName;
			if (!ClassHelper::exists($respondentClassName)) {
				return null;
			}
		}

		$respondent = $this->app->diProcessor->create($respondentClassName, [
			'plugin' => $plugin
		]);
		return $respondent;
	}

	/**
	 * @return File|null
	 */
	public function getJsMain()
	{
		$jsMain = $this->getPlugin()->getConfig('jsMain');
		if (!$jsMain) {
			return null;
		}

		$result = $this->getFile($jsMain);
		if ($result instanceof File) {
			return $result;
		}

		return null;
	}

	/**
	 * @return File|null
	 */
	public function getJsBootstrap()
	{
		$jsBootstrap = $this->getPlugin()->getConfig('jsBootstrap');
		if (!$jsBootstrap) {
			return null;
		}

		$result = $this->getFile($jsBootstrap);
		if ($result instanceof File) {
			return $result;
		}

		return null;
	}

	/**
	 * @param string $name
	 * @return string
	 */
	public function getAssetPath($name)
	{
		if (preg_match('/^http/', $name) || preg_match('/^\//', $name)) {
			return $name;
		}

		return '/' . $this->app->conductor->getRelativePath($this->getFullPath($name));
	}

	/**
	 * @return array
	 */
	public function getCssAssets()
	{
		$appCycler = $this->app->lifeCycle;
		if ($appCycler) {
			$appCycler->beforeGetPluginCssAssets($this->getPlugin());
		}

		$plugin = $this->getPlugin();
		$css = $plugin->getConfig('css');
		if (!$css) {
			return [];
		}

		$css = (array)$css;
		$result = [];
		foreach ($css as $value) {
			$path = $plugin->conductor->getFullPath($value);
			$d = new Directory($path);
			if (!$d->exists()) {
				return [];
			}

			$relativePath = lx::$app->conductor->getRelativePath($path);
			$files = $d->getFiles('*.css');
			$list = [];
			foreach ($files as $file) {
				$list[] = '/' . $relativePath . '/' . $file->getName();
			}

			$result = array_merge($result, $list);
		}

		return $result;
	}

	/**
	 * @return array
	 */
	public function getImagePathesInSite()
	{
		$images = $this->getPlugin()->getConfig('images');
		if ($images === null) {
			return [];
		}

		if (is_string($images)) {
			$images = ['default' => $images];
		}

		foreach ($images as $key => $path) {
			$relPath = '/' . $this->app->conductor->getRelativePath($this->getFullPath($path));
			$images[$key] = $relPath;
		}

		return $images;
	}


	/*******************************************************************************************************************
	 * PRIVATE
	 ******************************************************************************************************************/

	/**
	 * @param string $path
	 * @return string
	 */
	private function decodeAlias($path)
	{
		$aliases = $this->getPlugin()->getConfig('aliases');
		if (!$aliases) {
			return $this->getPlugin()->getService()->getFilePath($path);
		}

		$result = $path;
		while (true) {
			preg_match_all('/^@([_\w][_\-\w\d]*?)(\/|$)/', $result, $arr);
			if (empty($arr) || empty($arr[0])) {
				return $result;
			}

			$mask = $arr[0][0];
			$alias = $arr[1][0];
			if (!array_key_exists($alias, $aliases)) {
				return $result;
			}

			$alias = $aliases[$alias] . $arr[2][0];
			$result = str_replace($mask, $alias, $result);
		}
	}
}
