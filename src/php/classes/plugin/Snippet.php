<?php

namespace lx;

class Snippet extends ApplicationTool {
	public
		$renderIndex = null,
		$innerSnippetKeys = [],
		$renderParams = [],
		$clientParams = [];

	protected
		$file,  // файл с исходным кодом

		$dependencies,
		$fileDependencies,
		$self = [],  // свойства виджета самого блока (стили, атрибуты, поля) - собирают инфу из $widget при рендеринге
		$htmlContent = '',  // верстка содержимого
		$lx = [],    // пояснительная записка к содержимому
		$js = null;  // js-код сниппета для выполнения на стороне клиента

	public function __construct($context, $data) {
		parent::__construct($context->app);

		$this->snippetBuildContext = $context;
		$this->pluginBuildContext = $context->getPluginBuildContext();
		$this->parent = false;

		$this->renderParams = $data['renderParams'] ?? [];
		$this->clientParams = $data['clientParams'] ?? [];
		$this->renderIndex = $data['index'];

		$this->retrieveFile($data);
	}

	public function getFile() {
		return $this->file;
	}
	
	public function setDependencies($dependencies, $files) {
		$this->dependencies = $dependencies;
		$this->fileDependencies = $files;
	}

	public function getDependencies() {
		return $this->dependencies;
	}

	public function getFileDependencies() {
		return $this->fileDependencies;
	}

	public function getBuildData() {
		return [
			'filePath' => $this->file->getPath(),
			'renderParams' => $this->renderParams,
			'clientParams' => $this->clientParams,
		];
	}
	
	public function getPlugin() {
		return $this->pluginBuildContext->getPlugin();
	}

	/**
	 * @param $data
	 */
	public function applyBuildData($data) {
		$this->clientParams = $data['clientParams'];
		$this->self = $data['selfData'];
		$this->htmlContent = $data['html'];
		$this->lx = $data['lx'];
		$this->js = $data['js'];

		$this->runBuildProcess();
	}

	/**
	 * Получить консолидированный результат рендеринга
	 * */
	public function getData() {
		$hasContent = function($field) {
			return !($field === [] || $field === '' || $field === null);
		};

		$result = [];
		if ($hasContent($this->self)) $result['self'] = $this->self;
		if ($hasContent($this->htmlContent)) $result['html'] = $this->htmlContent;
		if ($hasContent($this->lx)) $result['lx'] = $this->lx;
		if ($hasContent($this->js)) $result['js'] = $this->js;
		return $result;
	}

	private function retrieveFile($data) {
		$path = $data['path'];
		if (!file_exists($path) || is_dir($path)) {
			$path = $this->tryFindPath($path);
		}

		if (!file_exists($path)) {
			// Блок не найден
			return;
		}

		$this->file = new File($path);
	}

	private function tryFindPath($path) {
		$arr = explode('/', $path);
		$name = end($arr);
		if (file_exists($path . '/_' . $name . '.js')) {
			return $path . '/_' . $name . '.js';
		}
		if (file_exists($path . '/' . $name . '.js')) {
			return $path . '/' . $name . '.js';
		}

		if (file_exists($path . '/_main.js')) {
			return $path . '/_main.js';
		}
		if (file_exists($path . '/main.js')) {
			return $path . '/main.js';
		}

		if (file_exists($path . '.js')) {
			return $path . '.js';
		}

		return false;
	}

	private function runBuildProcess() {
		foreach ($this->lx as &$elemData) {
			// Внедрение сниппета в элемент
			if (isset($elemData['snippetInfo'])) {
				$snippetInfo = $elemData['snippetInfo'];
				unset($elemData['snippetInfo']);

				$snippet = $this->addInnerSnippet($snippetInfo);
				if ($snippet !== null) {
					$elemData['ib'] = $snippet->renderIndex;
					if (!empty($snippet->clientParams)) {
						$elemData['isp'] = $snippet->clientParams;
					}
				}
			}
		}
		unset($elemData);
	}

	/**
	 * Добавление вложенного блока
	 * @param $snippetInfo
	 * @return mixed
	 */
	private function addInnerSnippet($snippetInfo) {
		$path = $snippetInfo['path'];
		$renderParams = $snippetInfo['renderParams'];
		$clientParams = $snippetInfo['clientParams'];

		if (is_string($path)) {
			$fullPath = $this->getPlugin()
				->conductor
				->getFullPath($path, $this->file->getParentDirPath());
		} else {
			if (isset($path['plugin'])) {
				if (!isset($path['snippet'])) {
					return null;
				}

				$plugin = $this->app->getPlugin($path['plugin']);
				if (!$plugin) {
					return null;
				}
				
				$fullPath = $plugin->conductor->getSnippetPath($path['snippet']);
			}
		}

		if (!$this->tryFindPath($fullPath)) {
			return null;
		}

		$snippet = $this->snippetBuildContext->addSnippet([
			'path' => $fullPath,
			'renderParams' => $renderParams,
			'clientParams' => $clientParams,
		]);
		$this->innerSnippetKeys[] = $snippet->renderIndex;
			
		return $snippet;
	}
}
