<?php

namespace lx;

use lx;

class PluginBuildContext implements ContextTreeInterface
{
	use ContextTreeTrait;

	const DEFAULT_PLUGIN_TITLE = 'lx';

	private Plugin $plugin;
	private string $cacheType;
	private SnippetBuildContext $rootSnippetBuildContext;
	private bool $compiled;
	private string $pluginInfo;
	private string $mainJs;
	private string $snippetData;
	private string $commonData;
    /** @var JsScriptAsset[] */
	private array $scripts = [];
	private array $css = [];
	private JsCompiler $jsCompiler;
	private array $moduleDependencies = [];
	private array $commonModuleDependencies = [];

	protected function afterObjectConstruct(iterable $config): void
	{
		$this->plugin = $config['plugin'];
		$this->compiled = false;
		$this->jsCompiler = new PluginFrontendJsCompiler($this->getPlugin());
        $this->jsCompiler->setBuildModules(false);

		$moduleDependencies = $this->plugin->getModuleDependencies();
		if (!empty($moduleDependencies)) {
			$this->noteModuleDependencies($moduleDependencies);
		}
	}

	public function getPlugin(): Plugin
	{
		return $this->plugin;
	}

	public function getCacheType(): string
	{
		return $this->cacheType ?? $this->getPlugin()->getConfig('cacheType') ?? PluginCacheManager::CACHE_NONE;
	}

	public function buildCache(): void
	{
		$this->cacheType = PluginCacheManager::CACHE_BUILD;
		$this->compile();
	}

	public function build(): array
	{
		$this->compile();

		$result = [
			'pluginInfo' => '',
			'modules' => $this->commonModuleDependencies,
		];

		$allScripts = [];
		$allCss = [];
		$this->eachContext(function (PluginBuildContext $context) use (&$result, &$allScripts, &$allCss) {
			$key = $context->getKey();
			$result['pluginInfo'] .= "<plugin $key>{$context->commonData}</plugin $key>";

			if (!empty($context->scripts)) {
				$allScripts = array_merge($allScripts, $context->scripts);
			}

			if (!empty($context->css)) {
				$allCss = array_merge($allCss, $context->css);
			}
		});

        $plugin = $this->getPlugin();
		$result['page'] = [
			'title' => I18nHelper::localizePlugin(
                $plugin, 
                $plugin->title ? $plugin->title : self::DEFAULT_PLUGIN_TITLE
            ),
			'icon' => $plugin->icon,
			'scripts' => $this->collapseScripts($allScripts),
			'css' => $this->collapseCss($allCss),
		];

		return $result;
	}

	public function applayDependencies(array $dependencies): void
	{
		if (isset($dependencies['plugins'])) {
			foreach ($dependencies['plugins'] as $pluginInfo) {
                $plugin = lx::$app->pluginProvider->getPluginByConfig($pluginInfo);
				$plugin->setAnchor($pluginInfo['anchor']);
				$context = $this->add(['plugin' => $plugin]);
				$context->compile();
			}
		}

		if (isset($dependencies['modules'])) {
			$this->noteModuleDependencies($dependencies['modules']);
		}

		if (isset($dependencies['scripts'])) {
			foreach ($dependencies['scripts'] as $script) {
				$this->getPlugin()->addScript($script);
			}
		}

		if (isset($dependencies['i18n'])) {
			foreach ($dependencies['i18n'] as $config) {
				lx::$app->useI18n($config);
			}
		}
	}


	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * COMPILE METHODS
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    private function compile(): void
    {
        if ($this->compiled) {
            return;
        }

        $plugin = $this->getPlugin();

        $plugin->beforeCompile();
        $this->compileSnippet();
        $this->compileMainJs();

        $dependencies = $this->jsCompiler->getDependencies() ?? new JsCompileDependencies();

        $cssPresetModule = lx::$app->cssManager->getCssPresetModule($plugin->getCssPreset());
        if ($cssPresetModule) {
            $dependencies->addModule($cssPresetModule);
        }
        $this->applayDependencies($dependencies->toArray());

        $this->compilePluginInfo();

        $key = $this->getKey();
        $data = "<mi $key>{$this->pluginInfo}</mi $key>"
            . "<bl $key>{$this->snippetData}</bl $key>"
            . "<mj $key>{$this->mainJs}</mj $key>";
        $this->commonData = I18nHelper::localizePlugin($this->getPlugin(), $data);
        $this->applyCssPreset();

        $this->compiled = true;
        $plugin->afterCompile();
    }

    private function noteModuleDependencies(array $moduleNames): void
    {
        $this->moduleDependencies = array_values(array_unique(array_merge(
            $this->moduleDependencies,
            $moduleNames
        )));

        $head = $this->getHead();
        $head->commonModuleDependencies = array_values(array_unique(array_merge(
            $head->commonModuleDependencies,
            $moduleNames
        )));
    }

	private function compilePluginInfo(): void
	{
		$info = $this->getPluginInfo();

		// Root snippet key
		$info['rsk'] = $this->getPlugin()->getRootSnippetKey();

		// All plugin dependencies
        $assetProvider = new PluginAssetProvider($this->getPlugin());
		$this->scripts = $assetProvider->getPluginScripts();
		$this->css = $assetProvider->getPluginCss();

		$dependencies = [];
        if (!empty($this->moduleDependencies)) {
            $dependencies['m'] = $this->moduleDependencies;
            $this->css = array_merge(
                $this->css,
                lx::$app->jsModules->getModulesCss($this->moduleDependencies)
            );
        }

        if (!empty($this->css)) {
            $dependencies['c'] = $this->css;
        }

		if (!empty($this->scripts)) {
			$dependencies['s'] = [];
			foreach ($this->scripts as $script) {
				$dependencies['s'][] = $script->getPath();
			}
		}

		if (!ArrayHelper::isDeepEmpty($dependencies)) {
			$info['dep'] = $dependencies;
		}

		$this->pluginInfo = json_encode($info);
	}

    private function getPluginInfo(): array
    {
        $plugin = $this->getPlugin();
        $config = $plugin->getConfig();
        $info = [
            'name' => $plugin->name,
            'anchor' => $plugin->getAnchor(),
        ];

        $attributes = $plugin->attributes->getProperties();
        if (!empty($attributes)) {
            $info['attributes'] = $attributes;
        }

        if (isset($config['images'])) {
            $info['images'] = $plugin->getImagePathsList();
        }

        $widgetBasicCssList = $plugin->widgetBasicCssList();
        if (!empty($widgetBasicCssList)) {
            $info['wgdl'] = $widgetBasicCssList;
        }

        $info['cssPreset'] = $plugin->getCssPreset();

        $preseted = $plugin->getPresetedCssClasses();
        if (!empty($preseted)) {
            $info['preseted'] = $preseted;
        }

        return $info;
    }

	private function compileSnippet(): void
	{
		$this->rootSnippetBuildContext = new SnippetBuildContext(['pluginBuildContext' => $this]);
		$snippets = $this->rootSnippetBuildContext->build();
		$this->snippetData = $snippets;
	}

	private function compileMainJs(): void
	{
		$plugin = $this->getPlugin();
        $path = $plugin->conductor->getFullPath($plugin->getConfig('client'));
        $file = new File($path);
        $code = $file->exists() ? $file->get() : '';
        if ($code == '') {
            $this->mainJs = '';
            return;
        }

        $require = $plugin->getConfig('require');
        $core = $plugin->getConfig('core');
        $cssAssets = (lx::$app->cssManager->getBuildType() === CssManager::BUILD_TYPE_NONE)
            ? $plugin->getConfig('cssAssets')
            : null;
        $guiNodes = $plugin->getConfig('guiNodes');

        if ($require) {
            $requireStr = '';
            foreach ($require as $item) {
                $requireStr .= "#lx:require $item;";
            }
            $code = $requireStr . $code;
        }

        $initMethods = '';
        if ($core) {
            $initMethods .= "getCoreClass(){return $core;}";
        }

        if ($cssAssets) {
            $assetClasses = implode(',', $cssAssets);
            $initMethods .= "getCssAssetClasses(){return [$assetClasses];}";
        }

        if ($guiNodes) {
            $guiNodesObj = [];
            foreach ($guiNodes as $key => $class) {
                $guiNodesObj[] = "$key:$class";
            }
            $guiNodesObj = implode(',', $guiNodesObj);
            $initMethods .= 'getGuiNodeClasses(){return {' . $guiNodesObj . '};}';
        }

        if ($initMethods !== '') {
            $code = preg_replace('/(class Plugin[^{]*?{)/', '$1' . $initMethods, $code);
        }

        $code = $this->jsCompiler->compileCode(
            '#lx:public;' . $code,
            $file->exists() ? $file->getPath() : $this->plugin->directory->getPath()
        );
        $this->mainJs = '(config)=>{let __plugin__=null;' . $code . '__plugin__=new Plugin(config);return __plugin__;}';
	}

    /**
     * @param JsScriptAsset[] $scripts
     */
	private function collapseScripts(array $scripts): array
	{
		$result = [];
		foreach ($scripts as $script) {
			$path = $script->getPath();
			if (!array_key_exists($path, $result)) {
				$result[$path] = $script->toArray();
			}
		}

		return array_values($result);
	}

	private function collapseCss(array $css): array
	{
		$result = [];
		foreach ($css as $value) {
			if (array_search($value, $result) === false) {
				$result[] = $value;
			}
		}
		return $result;
	}

    private function applyCssPreset(): void
    {
        $preset = $this->getPlugin()->getCssPreset();
        $this->commonData = str_replace('#lx:preset:lx#', $preset, $this->commonData);
    }
}
