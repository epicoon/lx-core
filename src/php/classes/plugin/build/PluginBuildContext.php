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
		return $this->cacheType ?? $this->getPlugin()->getConfig('cacheType') ?? Plugin::CACHE_NONE;
	}

	public function buildCache(): void
	{
		$this->cacheType = Plugin::CACHE_BUILD;
		$this->compile();
	}

	public function build(): array
	{
		$this->compile();

		$plugin = $this->getPlugin();
		$title = $plugin->title ? $plugin->title : self::DEFAULT_PLUGIN_TITLE;
		$title = I18nHelper::localizePlugin($plugin, $title);
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

		$result['page'] = [
			'title' => $title,
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
        $cssPresetModule = lx::$app->presetManager->getCssPresetModule($plugin->getCssPreset());
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
        $this->getPlugin()->afterCompile();
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
		$info = $this->getPlugin()->getSelfInfo();

		// Root snippet key
		$info['rsk'] = $this->getPlugin()->getRootSnippetKey();

		// All plugin dependencies
		$this->scripts = $this->getPlugin()->getScripts();
		$this->css = $this->getPlugin()->getCss();
		$dependencies = [];
		if (!empty($this->scripts)) {
			$dependencies['s'] = [];
			foreach ($this->scripts as $script) {
				$dependencies['s'][] = $script['path'];
			}
		}
		if (!empty($this->css)) {
			$dependencies['c'] = $this->css;
		}
		if (!empty($this->moduleDependencies)) {
			$dependencies['m'] = $this->moduleDependencies;
		}
		if (!ArrayHelper::isDeepEmpty($dependencies)) {
			$info['dep'] = $dependencies;
		}

		$this->pluginInfo = json_encode($info);
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
        $code = $this->jsCompiler->compileCode(
            '#lx:public;' . $code,
            $file->exists() ? $file->getPath() : $this->plugin->directory->getPath()
        );
        $this->mainJs = '(info,el)=>{let __plugin__;' . $code . '__plugin__=new Plugin(info,el);return __plugin__;}';
	}

	private function collapseScripts(array $scripts): array
	{
		$result = [];
		foreach ($scripts as $value) {
			$path = $value['path'];
			if (!array_key_exists($path, $result)) {
				$result[$path] = $value;
			} else {
				if ($value['parallel'] ?? false) {
					$result[] = $value;
				}
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
