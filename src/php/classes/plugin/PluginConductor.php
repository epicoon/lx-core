<?php

namespace lx;

use lx;

class PluginConductor implements ConductorInterface
{
    private Plugin $plugin;
	private array $imageMap;
	private array $cssMap;

    public function setPlugin(Plugin $plugin): void
    {
        $this->plugin = $plugin;
    }

	public function getPlugin(): Plugin
	{
        return $this->plugin;
	}

	public function getPath(): string
	{
		return $this->getPlugin()->directory->getPath();
	}

	public function getFullPath(string $fileName, ?string $relativePath = null): ?string
	{
		if ($fileName[0] == '@') {
			$fileName = $this->decodeAlias($fileName);
		}

		if ($relativePath === null) {
			$relativePath = $this->getPath();
		}

		return lx::$app->conductor->getFullPath($fileName, $relativePath);
	}

	public function getRelativePath(string $path, ?string $defaultLocation = null): string
	{
		$fullPath = $this->getFullPath($path, $defaultLocation);
		return explode($this->getPath() . '/', $fullPath)[1];
	}

	public function getFile(string $name): ?BaseFile
	{
		$path = $this->getFullPath($name);
		if (!$path) {
			return null;
		}

		return BaseFile::construct($path);
	}

	public function getImagePath(string $fileName): ?string
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

	public function getSystemPath(string $name = null): string
	{
        $path = $this->getPath() . '/.system';
        if ($name) {
            return $path . '/' . $name;
        }

        return $path;
	}

    public function getLocalSystemPath(string $name = null): string
    {
        $path = lx::$conductor->getSystemPath('services') . '/'
            . lx::$app->conductor->getRelativePath($this->getPath(), lx::$app->sitePath);

        return $name ? "$path/$name" : $path;
    }

	public function getSnippetsCachePath(): string
	{
		return $this->getLocalSystemPath('snippet_cache');
	}

	public function pluginContains(string $path): bool
	{
		return $this->getPlugin()->directory->contains($path);
	}

	/**
	 * If [[$name]] isn't defined path to the root snippet will be returned
	 */
	public function getSnippetPath(?string $name = null): ?string
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
     * @return array<Directory>
     */
	public function getSnippetDirectories(): array
    {
        $snippetDirs = $this->getPlugin()->getConfig('snippets');
        if (!$snippetDirs) {
            return [];
        }

        $result = [];
        foreach ((array)$snippetDirs as $snippetDir) {
            $dir = $this->getFile($snippetDir);
            if ($dir && $dir->isDirectory()) {
                $result[] = $dir;
            }
        }
        return $result;
    }

	public function getRespondent(string $name): ?Respondent
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

        $respondentConfig = ['plugin' => $plugin];
		$respondent = lx::$app->diProcessor->create($respondentClassName, [$respondentConfig]);
		return $respondent;
	}

	public function getJsMain(): ?File
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

	public function getAssetPath(string $name): string
	{
		if (preg_match('/^http/', $name) || preg_match('/^\//', $name)) {
			return $name;
		}

		return '/' . lx::$app->conductor->getRelativePath($this->getFullPath($name));
	}

    /**
     * @return array<string>
     */
	public function getCssAssets(): array
	{
        if (lx::$app->presetManager->isBuildType(PresetManager::BUILD_TYPE_NONE)) {
            return [];
        }

		$css = (array)($this->getPlugin()->getConfig('css') ?? []);
        $css[]  = $this->getLocalSystemPath('css');
		$result = [];
		foreach ($css as $value) {
			$path = $this->getFullPath($value);
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
	 * @return array<string>
	 */
	public function getImagePathesInSite(): array
	{
		$images = $this->getPlugin()->getConfig('images');
		if ($images === null) {
			return [];
		}

		if (is_string($images)) {
			$images = ['default' => $images];
		}

        foreach ($images as $key => $path) {
            if (preg_match('/^(http:|https:)/', $path)) {
                $images[$key] = $path;
            } else {
                $relPath = '/' . lx::$app->conductor->getRelativePath($this->getFullPath($path));
                $images[$key] = $relPath;
            }
        }

		return $images;
	}


	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * PRIVATE
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	private function decodeAlias(string $path): string
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
