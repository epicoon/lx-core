<?php

namespace lx;

use lx;

class SnippetBuildContext implements ContextTreeInterface
{
	use ContextTreeTrait;

	private PluginBuildContext $pluginBuildContext;
	private Snippet $snippet;
	private SnippetCacheData $cacheData;

	protected function afterObjectConstruct(iterable $config): void
	{
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

	public function build(): string
	{
		if (!$this->snippet) {
			return [];
		}

		$buildType = $this->pluginBuildContext->getCacheType();
		switch ($buildType) {
			case PluginCacheManager::CACHE_BUILD:
				return $this->buildProcess(true);
			case PluginCacheManager::CACHE_NONE:
				return $this->buildProcess(false);
			case PluginCacheManager::CACHE_STRICT:
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
			case PluginCacheManager::CACHE_ON:
				return $this->getCache() ?? $this->buildProcess(true);
			case PluginCacheManager::CACHE_SMART:
				return $this->getSmartCache();
		}
	}

	public function addSnippet(array $snippetData): Snippet
	{
		$contex = $this->add([
			'pluginBuildContext' => $this->pluginBuildContext,
			'snippetData' => $snippetData,
		]);
		return $contex->getSnippet();
	}

	public function getPluginBuildContext(): PluginBuildContext
	{
		return $this->pluginBuildContext;
	}

	public function getPlugin(): Plugin
	{
		return $this->pluginBuildContext->getPlugin();
	}

	public function getSnippet(): Snippet
	{
		return $this->snippet;
	}


	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * PRIVATE
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	private function getCache(): ?string
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

	private function getSmartCache(): string
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

	private function buildProcess(bool $renewCache): string
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

	private function actualizeCache(array $changed): string
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
					'attributes' => $data['attributes'] ?? [],
				],
				'key' => $key,
			]);
			$context->runSnippetCode();
			$snippets = array_merge($snippets, $context->getSnippets());
		}

		$cacheData = $this->cacheData->smartRenew($changed, $snippets);
		$this->getPlugin()->setRootSnippetKey($cacheData['rootSnippetKey']);
		$this->getPlugin()->applyBuildData($cacheData['pluginModif']);

		/** @var JsCompileDependencies $dependencies */
		$dependencies = $cacheData['dependencies'];
		$this->pluginBuildContext->applayDependencies($dependencies->toArray());
		return $cacheData['cache'];
	}

	private function runSnippetCode(): void
	{
		$app = lx::$app;
		$plugin = $this->getPlugin();
		$snippet = $this->snippet;

		$appData = CodeConverterHelper::arrayToJsCode($app->getBuildData());
		$pluginData = CodeConverterHelper::arrayToJsCode($plugin->getBuildData());
		$snippetData = CodeConverterHelper::arrayToJsCode($snippet->getBuildData());

        $modules = $plugin->getModuleDependencies();
		$pre = "
		    lx.app.start($appData);
			lx.globalContext.App = lx.app;
			lx.globalContext.Plugin = new lx.Plugin($pluginData);
			lx.globalContext.Snippet = new lx.Snippet($snippetData);
		";
		$post = PHP_EOL . 'return {
			app: App.getResult(),
			plugin: Plugin.getResult(),
			snippet: Snippet.getResult(),
			dependencies: {}
				.lxMerge(App.getDependencies())
				.lxMerge(Plugin.getDependencies())
				.lxMerge(Snippet.getDependencies())
		};';

		$compiler = new JsCompiler($plugin->conductor, $plugin->moduleInjector);
		$compiler->setBuildModules(true);
		$executor = new NodeJsExecutor($compiler);
		$res = $executor
            ->setFile($snippet->getFile())
            ->setPrevCode($pre)
            ->setPostCode($post)
            ->setModules($modules)
            ->run();

        if (!$res) {
            throw new \Exception(
                $executor->getFirstFlightRecord()
                . PHP_EOL . 'Snippet file: ' . $snippet->getFile()->getPath() . PHP_EOL
            );
        }
        
		$app->applyBuildData($res['app']);
		$snippet->setPluginModifications($res['plugin']);
		$snippet->applyBuildData($res['snippet']);

		// Зависимости сниппету запомнить, к плагину применить
		$dependencies = $compiler->getDependencies();
		$dependencies->add($res['dependencies']);
		$dependencies = $dependencies->toArray();
		$snippet->setDependencies($dependencies, $compiler->getCompiledFiles());
		$this->pluginBuildContext->applayDependencies($dependencies);

		// Строится дерево контекстов с собранными сниппетами
        /** @var SnippetBuildContext $context */
        foreach ($this->nestedContexts as $context) {
			$context->runSnippetCode();
		}
	}

	private function createSnippet(array $snippetData): void
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
	 * @return array<Snippet>
	 */
	private function getSnippets(): array
	{
		$arr = [];
		$this->eachContext(function ($context) use (&$arr) {
			$arr[$context->getKey()] = $context->getSnippet();
		});
		return $arr;
	}
}
