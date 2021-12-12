<?php

namespace lx;

class AssetCompiler
{
	public static function getLinksMap(array $map): array
	{
		$result = [
			'origins' => [],
			'links' => [],
			'names' => [],
		];
		foreach ($map as $key => $value) {
			if (!preg_match('/^(\/web\/|http:|https:)/', $value)) {
				preg_match('/\.[^.\/]+$/', $value, $ext);
				$ext = $ext[0] ?? null;
				if ($ext == '.css') {
					$parentDir = dirname($value);
					$file = basename($value);
					$path = '/web/auto/' . md5($parentDir);
					$result['origins'][$key] = $parentDir;
					$result['links'][$key] = $path;
					$result['names'][$key] = $path . '/' . $file;
				} elseif ($ext) {
					$path = '/web/auto/' . md5($value);
					$result['origins'][$key] = $value;
					$result['links'][$key] = $path . $ext;
					$result['names'][$key] = $path . $ext;
				} else {
					$path = '/web/auto/' . md5($value);
					$result['origins'][$key] = $value;
					$result['links'][$key] = $path;
					$result['names'][$key] = $path;
				}
			} else {
				$result['names'][$key] = $value;
			}
		}

		return $result;
	}

	public function makePluginsAssetLinks(): void
	{
		$services = PackageBrowser::getServicesList();
		foreach ($services as $service) {
			$plugins = $service->getStaticPlugins();
			foreach ($plugins as $plugin) {
				$origins = $plugin->getOriginScripts();
				$arr = [];
				foreach ($origins as $value) {
					$arr[] = $value['path'];
				}
				$linksMap = self::getLinksMap($arr);
				$this->createLinks($linksMap['origins'], $linksMap['links']);

				$origins = $plugin->getOriginCss();
				$linksMap = self::getLinksMap($origins);
				$this->createLinks($linksMap['origins'], $linksMap['links']);

				$origins = $plugin->getOriginImagePathes();
				$linksMap = self::getLinksMap($origins);
				$this->createLinks($linksMap['origins'], $linksMap['links']);
			}
		}
	}

	public function createLinks(array $originalPathes, array $linkPathes): void
	{
		$sitePath = \lx::$conductor->sitePath;
		foreach ($linkPathes as $key => $path) {
			$origin = $originalPathes[$key];
			if ($origin == $path || !file_exists($sitePath . $origin)) {
				continue;
			}

			$fullPath = $sitePath . $path;
			$link = new FileLink($fullPath);
			if (!$link->exists()) {
				$origin = BaseFile::construct($sitePath . $originalPathes[$key]);
				$dir = $link->getParentDir();
				$dir->make();
				$link->create($origin);
			}
		}
	}

	public function copyLxCss(): void
	{
		$coreCssPath = \lx::$conductor->core . '/css';
		$dir = new \lx\Directory($coreCssPath);

		$webCssPath = \lx::$conductor->webCss;
		$dir->clone($webCssPath);
	}

	public function compileCssInDirectory(string $directoryPath): void
	{
		$d = new Directory($directoryPath);
		if (!$d->exists()) {
			return;
		}

		$files = $d->getFiles('*.(css|css.js)');
		$pares = [];
		$files->each(function ($file) use (&$pares) {
			$ext = $file->getExtension();
			$key = ($ext == 'css')
				? $file->getName()
				: $file->getCleanName();
			if (!array_key_exists($key, $pares)) {
				$pares[$key] = [
					'js' => null,
					'css' => null,
				];
			}
			if ($ext == 'css') {
				$pares[$key]['css'] = $file;
			} else {
				$pares[$key]['js'] = $file;
			}
		});

		foreach ($pares as $key => $pare) {
			if ($pare['js']
				&& (!$pare['css'] || $pare['js']->isNewer($pare['css']))
			) {
				if (!$pare['css']) {
					$pare['css'] = $d->makeFile($key);
				}

                $compiler = new JsCompiler();
                $compiler->setBuildModules(true);
                $exec = new NodeJsExecutor($compiler);
                $cssCode = $exec->setFile($pare['js'])->run();
				$pare['css']->put($cssCode);
			}
		}
	}

	public function compileLxCss(): void
	{
		$path = \lx::$conductor->webCss;
        $cssJsFile = new File($path . '/main.css.js');
        if (!$cssJsFile->exists()) {
            $this->copyLxCss();
        }

		$cssFile = new File($path . '/main.css');
		if (!$cssFile->exists() || $cssJsFile->isNewer($cssFile)) {
		    $compiler = new JsCompiler();
		    $compiler->setBuildModules(true);
			$exec = new NodeJsExecutor($compiler);
            $res = $exec->setFile($cssJsFile)->run();
            if ($res !== false) {
                $cssFile->put($res);
            }
		}
	}

	public function compileJsCore(): string
	{
		$path = \lx::$conductor->jsClientCore;
		$code = file_get_contents($path);

		$jsCompiler = new JsCompiler();
		$code = $jsCompiler->compileCode($code, $path);

        $servicesList = PackageBrowser::getServicesList();
        $modules = [];
        foreach ($servicesList as $service) {
            $modules = array_merge($modules, $service->getJsModules());
        }
        if (!empty($modules)) {
            $modulesProvider = new JsModuleProvider();
            $code .= $modulesProvider->getModulesCode($modules);
        }

		if (\lx::$app->language) {
			$code .= 'lx.lang=' . CodeConverterHelper::arrayToJsCode(\lx::$app->language->getCurrentData()) . ';';
		}

		return $code;
	}
}
