<?php

namespace lx;

class PluginBuildContext extends ApplicationTool implements ContextTreeInterface {
	use ContextTreeTrait;

	const DEFAULT_MODULE_TITLE = 'lx';

	private $plugin;
	private $rootSnippetBuildContext;

	private $compiled;
	private $pluginInfo;
	private $bootstrapJs;
	private $mainJs;
	private $snippetData;
	private $commonData;

	private $scripts;
	private $css;

	private $jsCompiler;
	private $moduleDependencies;
	private $commonModuleDependencies;

	public function __construct($plugin, $parent = null) {
		parent::__construct($plugin->app);

		$this->plugin = $plugin;
		$this->compiled = false;
		$this->ContextTreeTrait($parent);
		$this->moduleDependencies = [];
		$this->jsCompiler = new JsCompiler($this->app, $this->getPlugin()->conductor);

		if (!$parent) {
			$this->commonModuleDependencies = [];
		}

		$moduleDependencies = $plugin->getModuleDependencies();
		if (!empty($moduleDependencies)) {
			$this->noteModuleDependencies($moduleDependencies);
		}
	}

	public function getPlugin() {
		return $this->plugin;
	}
	
	public function getJsCompiler() {
		return $this->jsCompiler;
	}

	public function noteModuleDependencies($moduleNames) {
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
	 * Собрать все контексты
	 */
	public function build() {
		$this->compile();

		$plugin = $this->getPlugin();
		$title = $plugin->title ? $plugin->title : self::DEFAULT_MODULE_TITLE;
		$title = I18nHelper::localizePlugin($plugin, $title);
		$result = [
			'title' => $title,
			'icon' => $plugin->icon,
			'pluginInfo' => '',
			'modules' => $this->commonModuleDependencies,
			'scripts' => [],
			'css' => []
		];

		$this->eachContext(function($context) use (&$result) {
			$key = $context->getKey();
			$result['pluginInfo'] .= "<plugin $key>{$context->commonData}</plugin $key>";

			if (!empty($context->scripts)) {
				$result['scripts'][$key] = $context->scripts;
			}

			if (!empty($context->css)) {
				$result['css'][$key] = $context->css;
			}
		});

		return $result;
	}

	/**
	 * Собираем только этот контекст
	 */
	public function compile() {
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

		$this->compileScripts();
		$this->compileCss();

		$this->compiled = true;
		$this->getPlugin()->afterCompile();
	}

	/**
	 * @param $dependencies JsCompileDependencies
	 */
	public function applayDependencies($dependencies) {
		$dependenciesArray = $dependencies->toArray();

		if (isset($dependenciesArray['plugins'])) {
			foreach ($dependenciesArray['plugins'] as $pluginInfo) {
				$plugin = $this->app->getPlugin($pluginInfo);
				$plugin->setAnchor($pluginInfo['anchor']);
				$context = $this->add($plugin);
				$context->compile();
			}
		}

		if (isset($dependenciesArray['modules'])) {
			$this->noteModuleDependencies($dependenciesArray['modules']);
		}

		if (isset($dependenciesArray['scripts'])) {
			foreach ($dependenciesArray['scripts'] as $script) {
				$this->getPlugin()->script($script);
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

	public function compilePluginInfo() {
		$info = $this->callPrivatePluginMethod('getSelfInfo');

		/* Подсовываем зависимости от js-модулей
		 * Это только для того, чтобы плагин знал о своих собственных зависимостях
		 * и, если плагин сброшен, модули тоже можно было сбросить
		 * На ajax-дозагрузку модулей по зависимостям это не влияет - для этого строится
		 * единая карта зависимостей для всех отрендеренных плагинов
		 */
		if (!empty($this->moduleDependencies)) {
			$info['modep'] = $this->moduleDependencies;
		}

		// Root snippet key
		$info['rsk'] = $this->getPlugin()->getRootSnippetKey();

		$this->pluginInfo = json_encode($info);
	}

	private function compileSnippet() {
		$this->rootSnippetBuildContext = new SnippetBuildContext($this);
		$snippets = $this->rootSnippetBuildContext->build();
		$this->snippetData = $snippets;
	}

	private function compileBootstrapJs() {
		$plugin = $this->getPlugin();
		$jsBootstrapFile = $plugin->conductor->getJsBootstrap();
		$this->bootstrapJs = $this->compileJs('', $jsBootstrapFile, '');
	}

	private function compileMainJs() {
		$plugin = $this->getPlugin();
		$this->mainJs = $this->compileJs(
			$this->prepareJs('getPreJs'),
			$plugin->conductor->getJsMain(),
			$this->prepareJs('getPostJs')
		);
	}

	private function prepareJs($method) {
		$result = '';
		$jsArr = $this->callPrivatePluginMethod($method);
		foreach ($jsArr as $js) {
			if (preg_match('/^\(\)=>/', $js)) $result .= preg_replace('/^\(\)=>/', '', $js);
			else {
				$file = $this->conductor->getJsFile($js);
				if (!$file) continue;
				$result .= $file->get();
			}
		}
		return $result;
	}

	private function compileJs($preCode, $file, $postCode) {
		$fileExists = $file && $file->exists();
		$code = '';
		if ($fileExists) {
			$code = $file->get();
		}

		$code = "$preCode$code$postCode";
		if ($code == '') return '';

		return $this->jsCompiler->compileCode(
			$code,
			$fileExists ? $file->getPath() : $this->plugin->directory->getPath()
		);
	}

	private function compileScripts() {
		$this->scripts = $this->callPrivatePluginMethod('getScripts');
	}

	private function compileCss() {
		$this->css = $this->callPrivatePluginMethod('getCss');
	}

	/**
	 * PHP не поддерживает дружественные классы, но есть выход
	 * */
	public function callPrivatePluginMethod($methodName, $args = []) {
		return ClassHelper::call($this->getPlugin(), $methodName, $args);
	}
}
