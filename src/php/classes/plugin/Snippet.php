<?php

namespace lx;

/**
 * Class Snippet
 * @package lx
 */
class Snippet extends BaseObject
{
	use ApplicationToolTrait;

	/** @var File */
	private $file;

	/** @var array */
	private $renderParams = [];

	/** @var array */
	private $clientParams = [];

	/** @var array */
	private $metaData = [];

	/** @var string */
	private $renderIndex = null;

	/** @var array */
	private $pluginModifications = [];

	/** @var array */
	private $self = [];

	/** @var string */
	private $htmlContent = '';

	/** @var array */
	private $lx = [];

	/** @var string */
	private $js = null;

	/** @var array */
	private $dependencies;

	/** @var array */
	private $fileDependencies;

	/** @var array */
	private $innerSnippetKeys = [];

	/**
	 * Snippet constructor.
	 * @param SnippetBuildContext $context
	 * @param array $data
	 */
	public function __construct($context, $data)
	{
		$this->snippetBuildContext = $context;
		$this->pluginBuildContext = $context->getPluginBuildContext();
		$this->parent = false;
		$this->renderParams = $data['renderParams'] ?? [];
		$this->clientParams = $data['clientParams'] ?? [];
		$this->renderIndex = $data['index'];

		$this->retrieveFile($data);
	}

	/**
	 * @param string $path
	 * @return string|null
	 */
	public static function defineSnippetPath($path)
	{
		if (file_exists($path) && !is_dir($path)) {
			return $path;
		}

		if (file_exists("$path.js")) return "$path.js";
		if (file_exists("$path/_main.js")) return "$path/_main.js";
		if (file_exists("$path/main.js")) return "$path/main.js";

		$arr = explode('/', $path);
		$snippetName = end($arr);
		if (file_exists("$path/_$snippetName.js")) return "$path/_$snippetName.js";
		if (file_exists("$path/$snippetName.js")) return "$path/$snippetName.js";

		return null;
	}

	/**
	 * @return Plugin
	 */
	public function getPlugin()
	{
		return $this->pluginBuildContext->getPlugin();
	}

	/**
	 * @return string
	 */
	public function getRenderIndex()
	{
		return $this->renderIndex;
	}

	/**
	 * @param array $data
	 */
	public function setPluginModifications($data)
	{
		$this->pluginModifications = $data;
	}

	/**
	 * @return array
	 */
	public function getPluginModifications()
	{
		return $this->pluginModifications;
	}

	/**
	 * @return array
	 */
	public function getRenderParams()
	{
		return $this->renderParams;
	}

	/**
	 * @return array
	 */
	public function getClientParams()
	{
		return $this->clientParams;
	}

	/**
	 * @return array
	 */
	public function getInnerSnippetKeys()
	{
		return $this->innerSnippetKeys;
	}

	/**
	 * @return File
	 */
	public function getFile()
	{
		return $this->file;
	}

	/**
	 * @param array $dependencies
	 * @param array $files
	 */
	public function setDependencies($dependencies, $files)
	{
		$this->dependencies = $dependencies;
		$this->fileDependencies = $files;
	}

	/**
	 * @return array
	 */
	public function getDependencies()
	{
		return $this->dependencies;
	}

	/**
	 * @return array
	 */
	public function getFileDependencies()
	{
		return $this->fileDependencies;
	}

	/**
	 * @return array
	 */
	public function getBuildData()
	{
		return [
			'filePath' => $this->file->getPath(),
			'renderParams' => $this->renderParams,
			'clientParams' => $this->clientParams,
		];
	}

	/**
	 * @param array $data
	 */
	public function applyBuildData($data)
	{
		if (!empty($data['clientParams'])) {
			$this->clientParams += $data['clientParams'];
		}

		$this->self = $data['selfData'];
		$this->htmlContent = $data['html'];
		$this->lx = $data['lx'];
		$this->js = $data['js'];
		$this->metaData = $data['meta'];

		$this->runBuildProcess();
	}

	/**
	 * Method returns rendering result
	 *
	 * @return array
	 */
	public function getData()
	{
		$hasContent = function ($field) {
			return !($field === [] || $field === '' || $field === null);
		};

		$result = [];
		if ($hasContent($this->clientParams)) $result['params'] = $this->clientParams;
		if ($hasContent($this->self)) $result['self'] = $this->self;
		if ($hasContent($this->htmlContent)) $result['html'] = $this->htmlContent;
		if ($hasContent($this->lx)) $result['lx'] = $this->lx;
		if ($hasContent($this->js)) $result['js'] = $this->js;
		if ($hasContent($this->metaData)) $result['meta'] = $this->metaData;
		return $result;
	}


	/*******************************************************************************************************************
	 * PRIVATE
	 ******************************************************************************************************************/

	/**
	 * @param array $data
	 */
	private function retrieveFile($data)
	{
		$path = self::defineSnippetPath($data['path']);
		if (!$path) {
			\lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
				'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
				'msg' => "Snippet '{$data['path']}' is not found",
				'plugin' => $this->getPlugin()->name,
				'data' => $data,
			]);
			return;
		}

		$this->file = new File($path);
	}

	/**
	 * Applying of different injections was added while snippet was rendered
	 */
	private function runBuildProcess()
	{
		foreach ($this->lx as &$elemData) {
			// Injection of snippet to element
			if (isset($elemData['snippetInfo'])) {
				$snippetInfo = $elemData['snippetInfo'];
				unset($elemData['snippetInfo']);

				$snippet = $this->addInnerSnippet($snippetInfo);
				if ($snippet !== null) {
					$elemData['ib'] = $snippet->getRenderIndex();
				}
			}
		}
		unset($elemData);
	}

	/**
	 * @param $snippetInfo
	 * @return mixed
	 */
	private function addInnerSnippet($snippetInfo)
	{
		$path = $snippetInfo['path'];
		$renderParams = $snippetInfo['renderParams'];
		$clientParams = $snippetInfo['clientParams'];

		if (is_string($path)) {
			$fullPath = $this->getPlugin()
				->conductor
				->getFullPath($path, $this->file->getParentDirPath());
			$fullPath = self::defineSnippetPath($fullPath);
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

		if (!$fullPath) {
			return null;
		}

		$snippet = $this->snippetBuildContext->addSnippet([
			'path' => $fullPath,
			'renderParams' => $renderParams,
			'clientParams' => $clientParams,
		]);
		$this->innerSnippetKeys[] = $snippet->getRenderIndex();

		return $snippet;
	}
}
