<?php

namespace lx;

/**
 * Class PluginBuildContext
 * @package lx
 */
class PluginBuildContext implements ContextTreeInterface
{
    use ObjectTrait;
	use ApplicationToolTrait;
	use ContextTreeTrait;

	const DEFAULT_MODULE_TITLE = 'lx';

	/** @var Plugin */
	private $plugin;

	/** @var string */
	private $cacheType;

	/** @var SnippetBuildContext */
	private $rootSnippetBuildContext;

	/** @var bool */
	private $compiled;

	/** @var string */
	private $pluginInfo;

	/** @var string */
	private $bootstrapJs;

	/** @var string */
	private $mainJs;

	/** @var string */
	private $snippetData;

	/** @var string */
	private $commonData;

	/** @var array */
	private $scripts;

	/** @var array */
	private $css;

	/** @var JsCompiler */
	private $jsCompiler;

	/** @var array */
	private $moduleDependencies = [];

	/** @var array */
	private $commonModuleDependencies = [];

	public function __construct(array $config = [])
	{
	    $this->__objectConstruct($config);

		$this->plugin = $config['plugin'];
		$this->compiled = false;
		$this->jsCompiler = new PluginFrontendJsCompiler($this->getPlugin());

		$moduleDependencies = $this->plugin->getModuleDependencies();
		if (!empty($moduleDependencies)) {
			$this->noteModuleDependencies($moduleDependencies);
		}
	}

	/**
	 * @return Plugin
	 */
	public function getPlugin()
	{
		return $this->plugin;
	}

	/**
	 * @return string
	 */
	public function getCacheType()
	{
		return $this->cacheType ?? $this->getPlugin()->getConfig('cacheType') ?? Plugin::CACHE_NONE;
	}

	/**
	 * @param array $moduleNames
	 */
	public function noteModuleDependencies($moduleNames)
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

	/**
	 * Rebuild plugin cache
	 */
	public function buildCache()
	{
		$this->cacheType = Plugin::CACHE_BUILD;
		$this->compile();
	}

	/**
	 * Build all contexts
	 *
	 * @return array
	 */
	public function build()
	{
		$this->compile();

		$plugin = $this->getPlugin();
		$title = $plugin->title ? $plugin->title : self::DEFAULT_MODULE_TITLE;
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

	/**
	 * Build this context
	 */
	public function compile()
	{
		$this->getPlugin()->beforeCompile();
		if ($this->compiled) {
			return;
		}

		$this->compileSnippet();

		$this->compileBootstrapJs();
		$this->compileMainJs();
		$this->applayDependencies($this->jsCompiler->getDependencies());

		$this->compilePluginInfo();

		$key = $this->getKey();
		$data = "<mi $key>{$this->pluginInfo}</mi $key>"
			. "<bs $key>{$this->bootstrapJs}</bs $key>"
			. "<bl $key>{$this->snippetData}</bl $key>"
			. "<mj $key>{$this->mainJs}</mj $key>";
		$this->commonData = I18nHelper::localizePlugin($this->getPlugin(), $data);

		$this->compiled = true;
		$this->getPlugin()->afterCompile();
	}

	/**
	 * @param JsCompileDependencies $dependencies
	 */
	public function applayDependencies($dependencies)
	{
		$dependenciesArray = $dependencies->toArray();

		if (isset($dependenciesArray['plugins'])) {
			foreach ($dependenciesArray['plugins'] as $pluginInfo) {
				$plugin = $this->app->getPlugin($pluginInfo);
				$plugin->setAnchor($pluginInfo['anchor']);
				$context = $this->add(['plugin' => $plugin]);
				$context->compile();
			}
		}

		if (isset($dependenciesArray['modules'])) {
			$this->noteModuleDependencies($dependenciesArray['modules']);
		}

		if (isset($dependenciesArray['scripts'])) {
			foreach ($dependenciesArray['scripts'] as $script) {
				$this->getPlugin()->addScript($script);
			}
		}

		if (isset($dependenciesArray['i18n'])) {
			foreach ($dependenciesArray['i18n'] as $config) {
				$this->app->useI18n($config);
			}
		}
	}


	/*******************************************************************************************************************
	 * COMPILE METHODS
	 ******************************************************************************************************************/

	/**
	 * Building of plugin self information
	 */
	private function compilePluginInfo()
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
		if (!ArrayHelper::deepEmpty($dependencies)) {
			$info['dep'] = $dependencies;
		}

		$this->pluginInfo = json_encode($info);
	}

	/**
	 * Building of root snippet
	 */
	private function compileSnippet()
	{
		$this->rootSnippetBuildContext = new SnippetBuildContext(['pluginBuildContext' => $this]);
		$snippets = $this->rootSnippetBuildContext->build();
		$this->snippetData = $snippets;
	}

	/**
	 * Building of bootstrap JS-code
	 */
	private function compileBootstrapJs()
	{
		$plugin = $this->getPlugin();
		$jsBootstrapFile = $plugin->conductor->getJsBootstrap();
		$this->bootstrapJs = $this->compileJs($jsBootstrapFile);
	}

	/**
	 * Building of main JS-code
	 */
	private function compileMainJs()
	{
		$plugin = $this->getPlugin();
		$this->mainJs = $this->compileJs($plugin->conductor->getJsMain());
	}

	/**
	 * @param File $file
	 * @return string
	 */
	private function compileJs($file)
	{
		$fileExists = $file && $file->exists();
		$code = '';
		if ($fileExists) {
			$code = $file->get();
		}

		if ($code == '') return '';

		return $this->jsCompiler->compileCode(
			$code,
			$fileExists ? $file->getPath() : $this->plugin->directory->getPath()
		);
	}

	/**
	 * @param array $scripts
	 * @return array
	 */
	private function collapseScripts($scripts)
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

	/**
	 * @param array $css
	 * @return array
	 */
	private function collapseCss($css)
	{
		$result = [];
		foreach ($css as $value) {
			if (array_search($value, $result) === false) {
				$result[] = $value;
			}
		}
		return $result;
	}
}
