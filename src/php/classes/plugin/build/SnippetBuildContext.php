<?php

namespace lx;

/**
 * Class SnippetBuildContext
 * @package lx
 */
class SnippetBuildContext extends BaseObject implements ContextTreeInterface
{
	use ApplicationToolTrait;
	use ContextTreeTrait;

	/** @var PluginBuildContext */
	private $pluginBuildContext;

	/** @var Snippet */
	private $snippet;

	/** @var SnippetCacheData */
	private $cacheData;

	/**
	 * SnippetBuildContext constructor.
	 * @param array $config
	 */
	public function __construct($config)
	{
		parent::__construct($config);

		$this->pluginBuildContext = $config['pluginBuildContext'];
		if ($this->isHead()) {
			$this->cacheData = new SnippetCacheData($this);
		}

		$snippetData = $config['snippetData'] ?? [];
		if ($snippetData instanceof Snippet) {
			$this->snippet = $snippetData;
		} else {
			$this->createSnippet($snippetData);
		}
	}

	/**
	 * @return string
	 */
	public function build()
	{
		if (!$this->snippet) {
			return [];
		}

		$buildType = $this->pluginBuildContext->getCacheType();
		$this->cacheData->initBuildType($buildType);
		switch ($buildType) {
			case Plugin::CACHE_BUILD:
				return $this->buildProcess(true);
			case Plugin::CACHE_NONE:
				return $this->buildProcess(false);
			case Plugin::CACHE_STRICT:
				$result = $this->getCache();
				if (!$result) {
					\lx::devLog(['_' => [__FILE__, __CLASS__, __METHOD__, __LINE__],
						'__trace__' => debug_backtrace(
							DEBUG_BACKTRACE_PROVIDE_OBJECT & DEBUG_BACKTRACE_IGNORE_ARGS
						),
						'msg' => 'There is the strict cache option for the plugin without cache',
						'plugin' => $this->getPlugin()->name,
						'snippet' => $this->snippet->getFile()->getName(),
					]);
					$result = '[]';
				}
				return $result;
			case Plugin::CACHE_ON:
				return $this->getCache() ?? $this->buildProcess(true);
			case Plugin::CACHE_SMART:
				return $this->getSmartCache();
		}
	}

	/**
	 * @param array $snippetData
	 * @return Snippet
	 */
	public function addSnippet($snippetData)
	{
		$contex = $this->add([
			'pluginBuildContext' => $this->pluginBuildContext,
			'snippetData' => $snippetData,
		]);
		return $contex->getSnippet();
	}

	/**
	 * @return PluginBuildContext
	 */
	public function getPluginBuildContext()
	{
		return $this->pluginBuildContext;
	}

	/**
	 * @return Plugin
	 */
	public function getPlugin()
	{
		return $this->pluginBuildContext->getPlugin();
	}

	/**
	 * @return Snippet
	 */
	public function getSnippet()
	{
		return $this->snippet;
	}


	/*******************************************************************************************************************
	 * PRIVATE
	 ******************************************************************************************************************/

	/**
	 * @return array|null
	 */
	private function getCache()
	{
		$cacheData = $this->cacheData->get();
		if (!$cacheData) {
			return null;
		}

		$this->getPlugin()->setRootSnippetKey($cacheData['rootSnippetKey']);
		$this->getPlugin()->applyBuildData($cacheData['pluginModif']);
		$this->pluginBuildContext->applayDependencies($cacheData['dependencies']);
		return $cacheData['cache'];
	}

	/**
	 * @return array
	 */
	private function getSmartCache()
	{
		if ($this->cacheData->isEmpty()) {
			return $this->buildProcess(true);
		}

		$changed = $this->cacheData->getDiffs();
		if (empty($changed)) {
			return $this->getCache();
		}

		return $this->actualizeCache($changed);
	}

	/**
	 * @param bool $renewCache
	 * @return string
	 */
	private function buildProcess($renewCache)
	{
		$this->getPlugin()->setRootSnippetKey($this->getKey());
		$this->runSnippetCode();
		$this->getPlugin()->applyBuildData($this->snippet->getPluginModifications());

		$snippets = $this->getSnippets();
		$snippetsData = [];
		foreach ($snippets as $key => $snippet) {
			$snippetsData[$key] = $snippet->getData();
		}
		$result = json_encode($snippetsData);

		if ($renewCache) {
			$this->cacheData->renew(
				$this->getKey(),
				$snippets,
				$snippetsData,
				$result
			);
		}

		return $result;
	}

	/**
	 * @param array $changed
	 * @return array
	 */
	private function actualizeCache($changed)
	{
		$plugin = $this->getPlugin();
		$cacheMap = $this->cacheData->getMap();
		$snippets = [];
		foreach ($changed as $key) {
			$data = $cacheMap['map'][$key];
			$context = new SnippetBuildContext([
				'pluginBuildContext' => $this->pluginBuildContext,
				'snippetData' => [
					'path' => $plugin->getFilePath($data['path']),
					'renderParams' => $data['renderParams'] ?? [],
					'clientParams' => $data['clientParams'] ?? [],
				],
				'key' => $key,
			]);
			$context->runSnippetCode();
			$snippets = array_merge($snippets, $context->getSnippets());
		}

		$cacheData = $this->cacheData->smartRenew($changed, $snippets);
		$this->getPlugin()->setRootSnippetKey($cacheData['rootSnippetKey']);
		$this->getPlugin()->applyBuildData($cacheData['pluginModif']);
		$this->pluginBuildContext->applayDependencies($cacheData['dependencies']);
		return $cacheData['cache'];
	}

	/**
	 * Main build process
	 */
	private function runSnippetCode()
	{
		$app = $this->app;
		$plugin = $this->getPlugin();
		$snippet = $this->snippet;

		$appData = ArrayHelper::arrayToJsCode($app->getBuildData());
		$pluginData = ArrayHelper::arrayToJsCode($plugin->getBuildData());
		$snippetData = ArrayHelper::arrayToJsCode($snippet->getBuildData());

		$requires = [
			'@core/js/server/Application',
			'@core/js/server/Plugin',
			'@core/js/server/Snippet',

			'@core/js/server/file/',
			'@core/js/server/hash/HashMd5',

			'@core/js/helpers/Math',
			'@core/js/helpers/Geom',

			'@core/js/classes/Object',
			'@core/js/tools/Collection',
			'@core/js/tools/Tree',
			'@core/js/classes/DomElementDefinition',
			'@core/js/classes/Tag',
			'@core/js/classes/bit/BitLine',
			'@core/js/classes/bit/BitMap',
			'@core/js/components/Date',
		];
		$modules = array_merge([
			'lx.Box',
		], $plugin->getModuleDependencies());
		$pre = "
			lx.globalContext.App = new lx.Application($appData);
			lx.globalContext.Plugin = new lx.Plugin($pluginData);
			lx.globalContext.Snippet = new lx.Snippet($snippetData);
		";
		$post = 'return {
			app: App.getResult(),
			plugin: Plugin.getResult(),
			snippet: Snippet.getResult(),
			dependencies: {}
				.lxMerge(App.getDependencies())
				.lxMerge(Plugin.getDependencies())
				.lxMerge(Snippet.getDependencies())
		};';

		$compiler = new JsCompiler($plugin->conductor);
		$compiler->setBuildModules(true);
		$executor = new NodeJsExecutor($compiler);
		$res = $executor->run([
			'file' => $snippet->getFile(),
			'requires' => $requires,
			'modules' => $modules,
			'prevCode' => $pre,
			'postCode' => $post,
		]);

		$app->applyBuildData($res['app']);
		$snippet->setPluginModifications($res['plugin']);
		$snippet->applyBuildData($res['snippet']);

		// Зависимости сниппету запомнить, к плагину применить
		$dependencies = $compiler->getDependencies();
		$dependencies->add($res['dependencies']);
		$snippet->setDependencies($dependencies, $compiler->getCompiledFiles());
		$this->pluginBuildContext->applayDependencies($dependencies);

		// Строится дерево контекстов с собранными сниппетами
		foreach ($this->nestedContexts as $context) {
			$context->runSnippetCode();
		}
	}

	/**
	 * @param array $snippetData
	 */
	private function createSnippet($snippetData)
	{
		if (!isset($snippetData['path'])) {
			$path = $this->getPlugin()->conductor->getSnippetPath();
			if (!$path) {
				return;
			}
			$snippetData['path'] = $path;
		}

		$snippetData['index'] = $this->getKey();

		$this->snippet = new Snippet($this, $snippetData);
	}

	/**
	 * Transform snippets tree to line array
	 *
	 * @return array
	 */
	private function getSnippets()
	{
		$arr = [];
		$this->eachContext(function ($context) use (&$arr) {
			$arr[$context->getKey()] = $context->getSnippet();
		});
		return $arr;
	}
}
