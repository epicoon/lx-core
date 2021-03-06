<?php

namespace lx;

use lx;

/**
 * Class Plugin
 * @package lx
 *
 * @property-read string $name
 * @property-read string $relativePath
 * @property-read string $prototype
 * @property-read PluginDirectory $directory
 * @property-read PluginConductor $conductor
 * @property-read I18nPluginMap $i18nMap
 */
class Plugin extends Resource implements FusionInterface
{
	use FusionTrait;

	const DEFAULT_RESOURCE_METHOD = 'run';
	const AJAX_SOURCE_METHOD = 'ajaxResponse';

	const CACHE_NONE = 'none';
	const CACHE_ON = 'on';
	const CACHE_STRICT = 'strict';
	const CACHE_BUILD = 'build';
	const CACHE_SMART = 'smart';

	/** @var string */
	public $title = null;

	/** @var string */
	public $icon = null;

	/** @var DataObject */
	public $attributes;

	/** @var Service */
	protected $service = null;

	/** @var string */
	protected $_name;

	/** @var string */
	protected $_path;

	/** @var string */
	protected $_prototype = null;

	/** @var array */
	protected $config;

	/** @var string */
	private $anchor;

	/** @var string */
	private $rootSnippetKey;

	/** @var array */
	private $dependencies = [];

	/** @var array */
	private $onLoadList = [];

	/** @var array */
	private $scripts = [];

	/** @var array */
	private $css = [];

	/**
	 * Plugin constructor.
	 * @param array $data
	 */
	public function __construct($data)
	{
	    parent::__construct($data);

		$this->service = $data['service'];
		$this->_name = $this->service->getID() . ':' . $data['name'];
		$this->_path = $data['path'];
		$this->attributes = new DataObject();
		$this->anchor = '_root_';

		if (isset($data['prototype'])) {
			$this->_prototype = $data['prototype'];
		}

		$config = $data['config'];
		$commonConfig = $this->app->getDefaultPluginConfig();
		ConfigHelper::preparePluginConfig($commonConfig, $config);
		$injections = $this->app->getConfig('configInjection');
		ConfigHelper::pluginInject($this->name, $this->prototype, $injections, $config);
		$this->config = $config;

		$this->initFusionComponents($this->getConfig('components'));
		$this->init();
	}

	/**
	 * Define in child
	 */
	protected function init()
	{
		// pass
	}

	/**
	 * Define in child
	 *
	 * @return array
	 */
	protected function widgetBasicCssList()
	{
		return [];
	}

	/**
	 * @param Service $service
	 * @param string $pluginName
	 * @param string $pluginPath
	 * @param string $prototype
	 * @return Plugin|null
	 */
	public static function create($service, $pluginName, $pluginPath, $prototype = null)
	{
		$dir = new PluginDirectory($pluginPath);
		if (!$dir->exists()) {
			return null;
		}
		$configFile = $dir->getConfigFile();
		$config = $configFile !== null
			? $configFile->get()
			: [];
		$pluginClass = $config['class'] ?? self::class;
		unset($config['class']);

		$data = [
			'service' => $service,
			'name' => $pluginName,
			'path' => lx::$app->conductor->getRelativePath($pluginPath),
			'config' => $config,
		];

		if ($prototype) {
			$data['prototype'] = $prototype;
		}

		$plugin = lx::$app->diProcessor->create($pluginClass, $data);

		return $plugin;
	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name)
	{
		switch ($name) {
			case 'name':
				return $this->_name;

			case 'relativePath':
				return $this->_path;

			case 'prototype':
				return $this->_prototype;
		}

		return $this->__objectGet($name);
	}

	/**
	 * @return array
	 */
	public function getDefaultFusionComponents()
	{
		return [
			'directory' => PluginDirectory::class,
			'conductor' => PluginConductor::class,
			'i18nMap' => I18nPluginMap::class,
		];
	}

	/**
	 * @return Service
	 */
	public function getService()
	{
		return $this->service;
	}

	/**
	 * @return Plugin|null
	 */
	public function getPrototypePlugin()
	{
		if ($this->prototype) {
			return $this->app->getPlugin($this->prototype);
		}

		return null;
	}

	/**
	 * @return Service|null
	 */
	public function getPrototypeService()
	{
		if ($this->prototype) {
			$serviceName = explode(':', $this->prototype)[0];
			return $this->app->getService($serviceName);
		}

		return null;
	}

	/**
	 * @return Plugin
	 */
	public function getRootPlugin()
	{
		if (!$this->prototype) {
			return $this;
		}

		return $this->app->getPlugin($this->prototype)->getRootPlugin();
	}

	/**
	 * @return Service
	 */
	public function getRootService()
	{
		if (!$this->prototype) {
			return $this->getService();
		}

		return $this->app->getPlugin($this->prototype)->getRootPlugin()->getService();
	}

	/**
	 * @return string
	 */
	public function getPath()
	{
		return $this->conductor->getPath();
	}

	/**
	 * @param string $fileName
	 * @return string
	 */
	public function getFilePath($fileName)
	{
		return $this->conductor->getFullPath($fileName);
	}

	/**
	 * @param string $name
	 * @return BaseFile|null
	 */
	public function getFile($name)
	{
		return $this->conductor->getFile($name);
	}

	/**
	 * @param string $name
	 * @return BaseFile|null
	 */
	public function findFile($name)
	{
		return $this->directory->find($name);
	}

    /**
     * @param string $name
     * @return File
     */
	public function createFile($name)
    {
        $fullName = $this->conductor->getFullPath($name);
        return new File($fullName);
    }

	/**
	 * @param string $key
	 * @return mixed
	 */
	public function getConfig($key = null)
	{
		if ($key === null) return $this->config;
		if (!isset($this->config[$key])) return null;
		return $this->config[$key];
	}

    /**
     * @return string|null
     */
	public function getTitle()
    {
        $title = $this->getConfig('title');
        if (!$title) {
            return null;
        }

        return I18nHelper::translate($title, $this->i18nMap);
    }

    /**
     * @return string|null
     */
    public function getIcon()
    {
        $icon = $this->getConfig('icon');
        if (!$icon) {
            return null;
        }

        return $this->app->conductor->getRelativePath($icon, lx::$app->sitePath);
    }

	/**
	 * @param string $name
	 * @return Respondent|null
	 */
	public function getRespondent($name)
	{
		return $this->conductor->getRespondent($name);
	}

	/**
	 * This method is used by ResourceContext for return Plugin as resource
	 *
	 * @param array $params
	 * @param UserInterface $user
	 * @return ResponseInterface
	 */
	public function run($params = [], $user = null)
	{
		$builder = new PluginBuildContext(['plugin' => $this]);
		$result = $builder->build();

		return $this->prepareResponse($result);
	}

	/**
	 * Define in child
	 *
	 * @param array $data
	 * @return ResponseInterface
	 */
	protected function ajaxResponse($data)
	{
		return $this->prepareErrorResponse('Resource not found', ResponseCodeEnum::NOT_FOUND);
	}

    /**
     * @return array
     */
	public function getCacheInfo()
    {
        return [
            'type' => $this->getConfig('cacheType'),
            'exists' => $this->cacheExists(),
        ];
    }

    /**
     * @return bool
     */
    public function cacheExists()
    {
        $dir = new Directory($this->conductor->getSnippetsCachePath());
        return $dir->exists();
    }

    /**
     * Build plugin cache if not exist
     */
    public function buildCache()
    {
        if (!$this->cacheExists()) {
            $this->renewCache();
        }
    }

	/**
	 * Renew plugin cache
	 */
	public function renewCache()
	{
		$builder = new PluginBuildContext(['plugin' => $this]);
		$builder->buildCache();
	}

	/**
	 * Drop plugin cache
	 */
	public function dropCache()
	{
		$dir = new Directory($this->conductor->getSnippetsCachePath());
		$dir->remove();
	}

	/**
	 * Define in child
	 *
	 * @param array $attributes
	 * @return array
	 */
	public function beforeAddAttributes($attributes)
	{
		return $attributes;
	}

	/**
	 * Define in child
	 *
	 * @param array $attributes
	 */
	public function afterAddAttributes($attributes)
	{
		// pass
	}

	/**
	 * Define in child
	 */
	public function beforeCompile()
	{
		// pass
	}

	/**
	 * Define in child
	 */
	public function afterCompile()
	{
		// pass
	}

	/**
	 * Define in child
	 */
	public function beforeSending()
	{
		// pass
	}

	/**
	 * Define in child
	 */
	public function beforeSuccessfulSending()
	{
		// pass
	}

	/**
	 * Define in child
	 */
	public function beforeFailedSending()
	{
		// pass
	}

	/**
	 * Define in child
	 */
	public function afterSuccessfulSending()
	{
		// pass
	}

	/**
	 * Define in child
	 */
	public function afterFailedSending()
	{
		// pass
	}

	/**
	 * Define in child
	 */
	public function afterSending()
	{
		// pass
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 */
	public function setConfig($name, $value)
	{
		$this->config[$name] = $value;
	}

	/**
	 * @param string $anchor
	 */
	public function setAnchor($anchor)
	{
		$this->anchor = $anchor;
	}

	/**
	 * @return string
	 */
	public function getAnchor()
	{
		return $this->anchor;
	}

	/**
	 * @param string $key
	 */
	public function setRootSnippetKey($key)
	{
		$this->rootSnippetKey = $key;
	}

	/**
	 * @return string
	 */
	public function getRootSnippetKey()
	{
		return $this->rootSnippetKey;
	}

	/**
	 * Method returns ResourceContext for ajax-request
	 *
	 * @param string $respondent
	 * @param array $data
	 * @return ResourceContext|false
	 */
	public function getResourceContext($respondent, $data)
	{
		if (!isset($data['attributes']) || !isset($data['data'])) {
			lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
				'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
				'msg' => "Wrong data in ajax-request for plugin '{$this->name}'",
			]);
			return false;
		}

		$this->attributes->setProperties($data['attributes']);
		$requestData = $data['data'];

		if ($respondent) {
			return $this->ajaxResponseByRespondent($respondent, $requestData);
		}

		return new ResourceContext([
			'object' => $this,
			'method' => self::AJAX_SOURCE_METHOD,
			'params' => [$requestData],
		]);
	}

	/**
	 * @param array $attributes
	 */
	public function addAttributes($attributes)
	{
		$attributes = $this->beforeAddAttributes($attributes);
		if ($attributes === false) {
			return;
		}

		foreach ($attributes as $key => $value) {
			$this->addAttribute($key, $value);
		}

		$this->afterAddAttributes($attributes);
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 */
	public function addAttribute($name, $value)
	{
		$this->attributes->$name = $value;
	}

	/**
	 * @param array|string $config
	 * @return $this
	 */
	public function addScript($config)
	{
		$asset = new JsScriptAsset($this, $config);
		$path = $asset->getPath();
		if ($path && !array_key_exists($path, $this->scripts)) {
			$this->scripts[$path] = $asset;
		}
		return $this;
	}

	/**
	 * @param string $path
	 * @return $this
	 */
	public function addCss($path)
	{
		$path = $this->conductor->getAssetPath($path);
		if ($path) {
			$this->css[$path] = true;
		}
		return $this;
	}

	/**
	 * @param string $code
	 */
	public function onLoad($code)
	{
		$this->onLoadList[] = $code;
	}

	/**
	 * @param array $list
	 */
	public function setDependencies($list)
	{
		$this->dependencies = $list;
	}

	/**
	 * @param array $dependencies
	 */
	public function addDependencies($dependencies)
	{
		$this->dependencies = ArrayHelper::mergeRecursiveDistinct(
			$this->dependencies,
			$dependencies
		);
	}

	/**
	 * @return array
	 */
	public function getModuleDependencies()
	{
		if (isset($this->dependencies['modules'])) {
			return $this->dependencies['modules'];
		}

		return [];
	}


	/*******************************************************************************************************************
	 * METHODS FOR BUILDER
	 ******************************************************************************************************************/

	/**
	 * @return array
	 */
	public function getBuildData()
	{
		$result = [
			'serviceName' => $this->service->name,
			'name' => $this->_name,
			'path' => $this->getPath(),
			'images' => $this->conductor->getImagePathesInSite(),

			'title' => $this->title,
			'icon' => $this->icon,
		];

		$attributes = $this->attributes->getProperties();
		if (!empty($attributes)) {
			$result['attributes'] = $attributes;
		}

		$widgetBasicCssList = $this->widgetBasicCssList();
		if (!empty($widgetBasicCssList)) {
			$result['widgetBasicCss'] = $widgetBasicCssList;
		}

		return $result;
	}

	/**
	 * @param array $data
	 */
	public function applyBuildData($data)
	{
		if (isset($data['title'])) {
			$this->title = $data['title'];
		}

		if (isset($data['icon'])) {
			$this->icon = $data['icon'];
		}

		if (isset($data['attributes'])) {
			foreach ($data['attributes'] as $key => $value) {
				$this->addAttribute($key, $value);
			}
		}

		if (isset($data['onLoad'])) {
			foreach ($data['onLoad'] as $code) {
				$this->onLoad($code);
			}
		}
	}

	/**
	 * @return array
	 */
	public function getSelfInfo()
	{
		$config = $this->config;
		$info = [
			'name' => $this->_name,
			'anchor' => $this->anchor,
		];

		$attributes = $this->attributes->getProperties();
		if (!empty($attributes)) {
			$info['attributes'] = $attributes;
		}

		if (!empty($this->onLoadList)) {
			$info['onLoad'] = $this->onLoadList;
		}

		if (isset($config['images'])) {
			$info['images'] = $this->getImagePathes();
		}

		$widgetBasicCssList = $this->widgetBasicCssList();
		if (!empty($widgetBasicCssList)) {
			$info['wgdl'] = $widgetBasicCssList;
		}

		return $info;
	}

	/**
	 * @return array
	 */
	public function getOriginScripts()
	{
		$assets = [
			'from-code' => [],
			'from-config' => [],
		];

		/** @var JsScriptAsset $script */
		foreach ($this->scripts as $script) {
			$assets['from-code'][] = $script->toArray();
		}

		$fromConfig = $this->getConfig('scripts-list');
		if ($fromConfig) {
			foreach ($fromConfig as $item) {
				$asset = new JsScriptAsset($this, $item);
				$assets['from-config'][] = $asset->toArray();
			}
		}

		$priopity = $this->getConfig('scripts-priopity') ?? ['from-code', 'from-config'];
		$result = [];
		foreach ($priopity as $key) {
			$result = array_merge($result, $assets[$key]);
		}
		return $result;
	}

	/**
	 * @return array
	 */
	public function getOriginCss()
	{
		$assets = [
			'from-code' => array_keys($this->css),
			'from-config-self' => $this->conductor->getCssAssets(),
			'from-config-list' => [],
		];
		$list = $this->getConfig('css-list') ?? [];
		foreach ($list as $item) {
			$assets['from-config-list'][] = $this->conductor->getAssetPath($item);
		}

		$priopity = $this->getConfig('css-priopity') ?? ['from-config-self', 'from-code', 'from-config-list'];
		$result = [];
		foreach ($priopity as $key) {
			$result = array_merge($result, $assets[$key]);
		}
		return $result;
	}

	/**
	 * @return array
	 */
	public function getOriginImagePathes()
	{
		return $this->conductor->getImagePathesInSite();
	}

	/**
	 * @return array
	 */
	public function getScripts()
	{
		$list = $this->getOriginScripts();
		$arr = [];
		foreach ($list as $value) {
			$arr[] = $value['path'];
		}

		$linksMap = AssetCompiler::getLinksMap($arr);
		$appCycler = $this->app->lifeCycle;
		if ($appCycler) {
			$appCycler->beforeReturnAutoLinkPathes($linksMap['origins'], $linksMap['links']);
		}

		foreach ($linksMap['names'] as $key => $name) {
			$list[$key]['path'] = $name;
		}

		return $list;
	}

	/**
	 * @return array
	 */
	public function getCss()
	{
		$list = $this->getOriginCss();
		$linksMap = AssetCompiler::getLinksMap($list);
		$appCycler = $this->app->lifeCycle;
		if ($appCycler) {
			$appCycler->beforeReturnAutoLinkPathes($linksMap['origins'], $linksMap['links']);
		}

		return $linksMap['names'];
	}

	/**
	 * @return array
	 */
	public function getImagePathes()
	{
		$list = $this->getOriginImagePathes();
		$linksMap = AssetCompiler::getLinksMap($list);
		$appCycler = $this->app->lifeCycle;
		if ($appCycler) {
			$appCycler->beforeReturnAutoLinkPathes($linksMap['origins'], $linksMap['links']);
		}

		return $linksMap['names'];
	}


	/*******************************************************************************************************************
	 * PRIVATE
	 ******************************************************************************************************************/

	/**
	 * @param string $respondentName
	 * @param array $respondentParams
	 * @return ResourceContext|false
	 */
	private function ajaxResponseByRespondent($respondentName, $respondentParams)
	{
		$respInfo = preg_split('/[^\w\d_]/', $respondentName);
		$respondent = $this->getRespondent($respInfo[0] ?? '');

		if (!$respondent) {
			lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
				'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
				'msg' => "Respondent '$respondentName' is not found",
			]);
			return false;
		}

		$methodName = $respInfo[1] ?? '';
		if (!method_exists($respondent, $methodName)) {
			lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
				'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
				'msg' => "Method '$methodName' for respondent '$respondentName' is not found",
			]);
			return false;
		}

		return new ResourceContext([
			'object' => $respondent,
			'method' => $methodName,
			'params' => $respondentParams,
		]);
	}
}
