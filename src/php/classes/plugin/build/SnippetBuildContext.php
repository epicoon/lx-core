<?php

namespace lx;

class SnippetBuildContext extends ApplicationTool implements ContextTreeInterface {
	use ContextTreeTrait;

	private $pluginBuildContext;
	private $snippet;
	private $tempFile;
	private $idCounter;
	private $keyCounter;
	private $autoParentStack;


	public function __construct($pluginBuildContext, $snippetData = [], $parent = null) {
		parent::__construct($pluginBuildContext->app);

		$this->pluginBuildContext = $pluginBuildContext;
		$this->idCounter = 0;
		$this->autoParentStack = new Vector();
		$this->ContextTreeTrait($parent);
		$this->createSnippet($snippetData);
	}

	public function __set($name, $val) {
		if ($name == 'autoParent') {
			if ($val === null) $this->resetAutoParent();
			else $this->setAutoParent($val);
		}
	}

	public function __get($name) {
		if ($name == 'autoParent') return $this->getAutoParent();
		return parent::__get($name);
	}

	public function build() {
		if (!$this->snippet) {
			return [];
		}

		$this->runSnippetCode();
		return $this->packSnippets();
	}

	public function addSnippet($snippetData) {
		$contex = $this->add($this->pluginBuildContext, $snippetData);
		return $contex->getSnippet();
	}

	public function getPluginBuildContext() {
		return $this->pluginBuildContext;
	}

	public function getPlugin() {
		return $this->pluginBuildContext->getPlugin();
	}

	public function getSnippet() {
		return $this->snippet;
	}

	/**
	 * Ключ для очередного рендерящегося элемента
	 */
	public function genWidgetKey() {
		return $this->getHead()->incWidgetKey();
	}

	/**
	 * Количество выданных ключей
	 */
	public function incWidgetKey() {
		return 'e' . Math::decChangeNotation($this->keyCounter++, 62);
	}

	/**
	 * Установить на вершину стека дефолтный родительский элемент
	 * */
	public function setAutoParent($el) {
		$this->autoParentStack->push($el);
	}

	/**
	 * Получить дефолтный родительский элемент
	 * */
	public function getAutoParent() {
		return $this->autoParentStack->last();
	}

	/**
	 * Отменить дефолтный родительский элемент, находящийся на вершине стека
	 * */
	public function popAutoParent() {
		return $this->autoParentStack->pop();
	}

	/**
	 * Сбросить весь стек дефолтных родительских элементов и установить переданный
	 * */
	public function resetAutoParent($elem=null) {
		$this->autoParentStack->reset();
		if ($elem !== null) $this->setAutoParent($elem);
	}


	/*******************************************************************************************************************
	 * PRIVATE
	 ******************************************************************************************************************/

	private function createSnippet($snippetData) {
		if (!isset($snippetData['path'])) {
			$plugin = $this->pluginBuildContext->getPlugin();
			$path = $plugin->conductor->getSnippetPath();
			if (!$path) {
				return;
			}
			$snippetData['path'] = $path;
		}

		$snippetData['index'] = $this->getKey();

		$this->snippet = new Snippet($this, $snippetData);
	}

	private function runSnippetCode() {
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

			'@core/js/server/hash/HashMd5',

			'@core/js/helpers/Math',
			'@core/js/helpers/Geom',
			'@core/js/helpers/WidgetHelper',

			'@core/js/classes/Object',
			'@core/js/tools/Collection',
			'@core/js/tools/Tree',
			'@core/js/classes/DomElementDefinition',
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
			snippet: Snippet.getResult()
		};';

		$compiler = new JsCompiler($this->app, $this->pluginBuildContext);
		$compiler->setBuildModules(true);
		$executor = new NodeJsExecutor($this->app, $compiler);
		$res = $executor->run([
			'file' => $snippet->getFile(),
			'requires' => $requires,
			'modules' => $modules,
			'prevCode' => $pre,
			'postCode' => $post,
		]);

		$app->applyBuildData($res['app']);
		$plugin->applyBuildData($res['plugin']);
		// Получаем html и пояснительную записку контента, дорабатываем её
		$snippet->applyBuildData($res['snippet']);

		// Строится дерево контекстов с собранными сниппетами
		foreach ($this->nestedContexts as $context) {
			$context->runSnippetCode();
		}
	}

	/**
	 * Консолидация данных по отрендеренным блокам
	 * */
	private function packSnippets() {
		$arr = [];
		$this->eachContext(function($context) use (&$arr) {
			$arr[$context->getKey()] = $context->getSnippet()->getData();
		});
		return $arr;
	}


	/*******************************************************************************************************************
	 * INNER
	 ******************************************************************************************************************/

	protected function genUniqKey() {
		return $this->getHead()->genId();
	}

	private function genId() {
		$id = $this->idCounter;
		$this->idCounter++;
		return $id;
	}
}
