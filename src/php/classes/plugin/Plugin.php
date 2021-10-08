<?php

namespace lx;

use lx;

/**
 * @property-read string $name
 * @property-read string $relativePath
 * @property-read string $prototype
 * @property-read PluginDirectory $directory
 * @property-read PluginConductor $conductor
 * @property-read PluginI18nMap $i18nMap
 */
class Plugin extends Resource implements FusionInterface
{
	use FusionTrait;

	const DEFAULT_RESOURCE_METHOD = 'run';
	const AJAX_RESOURCE_METHOD = 'handleAjaxResponse';

	const CACHE_NONE = 'none';
	const CACHE_ON = 'on';
	const CACHE_STRICT = 'strict';
	const CACHE_BUILD = 'build';
	const CACHE_SMART = 'smart';

	const EVENT_BEFORE_GET_AUTO_LINKS = 'pluginEventBeforeGetAutoLinks';
    const EVENT_BEFORE_GET_CSS_ASSETS = 'pluginEventBeforeGetCssAssets';

	public ?string $title = null;
	public ?string $icon = null;
	public DataObject $attributes;
	protected Service $service;
	protected string $_name;
	protected string $_path;
	protected ?string $_prototype = null;
	protected array $config;
	private string $anchor;
	private string $rootSnippetKey;
	private array $dependencies = [];
	private array $onLoadList = [];
	private array $scripts = [];
	private array $css = [];

	public function __construct(array $data)
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
		$commonConfig = lx::$app->getDefaultPluginConfig();
		ConfigHelper::preparePluginConfig($commonConfig, $config);
		$injections = lx::$app->getConfig('configInjection') ?? [];
		ConfigHelper::pluginInject($this->name, $this->prototype, $injections, $config);
		$this->config = $config;

		$this->initFusionComponents($this->getConfig('components') ?? []);
		$this->init();
	}

    /**
     * Define in child
     */
	protected function init(): void
	{
		// pass
	}

	/**
	 * Define in child
	 */
	protected function widgetBasicCssList(): array
	{
		return [];
	}

	public static function create(
	    Service $service,
        string $pluginName,
        string $pluginPath,
        ?string $prototype = null
    ): ?Plugin
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
	 * @return mixed
	 */
	public function __get(string $name)
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

	public function getDefaultFusionComponents(): array
	{
		return [
			'directory' => PluginDirectory::class,
			'conductor' => PluginConductor::class,
			'i18nMap' => PluginI18nMap::class,
		];
	}

	public function getService(): Service
	{
		return $this->service;
	}

	public function getPrototypePlugin(): ?Plugin
	{
		if ($this->prototype) {
			return lx::$app->getPlugin($this->prototype);
		}

		return null;
	}

	public function getPrototypeService(): ?Service
	{
		if ($this->prototype) {
			$serviceName = explode(':', $this->prototype)[0];
			return lx::$app->getService($serviceName);
		}

		return null;
	}

	public function getRootPlugin(): Plugin
	{
		if (!$this->prototype) {
			return $this;
		}

		return lx::$app->getPlugin($this->prototype)->getRootPlugin();
	}

	public function getRootService(): Service
	{
		if (!$this->prototype) {
			return $this->getService();
		}

		return lx::$app->getPlugin($this->prototype)->getRootPlugin()->getService();
	}

	public function getPath(): string
	{
		return $this->conductor->getPath();
	}

	public function getFilePath(string $fileName): string
	{
		return $this->conductor->getFullPath($fileName);
	}

    public function createFile(string $name): File
    {
        $fullName = $this->conductor->getFullPath($name);
        return new File($fullName);
    }

	public function getFile(string $name): ?CommonFileInterface
	{
		return $this->conductor->getFile($name);
	}

    public function prepareFile(string $name): CommonFileInterface
    {
        return $this->getFile($name) ?? $this->createFile($name);
    }

	public function findFile(string $name): ?CommonFileInterface
	{
		return $this->directory->find($name);
	}

	/**
	 * @return mixed
	 */
	public function getConfig(?string $key = null)
	{
		if ($key === null) return $this->config;
		if (!isset($this->config[$key])) return null;
		return $this->config[$key];
	}

	public function getTitle(): ?string
    {
        $title = $this->getConfig('title');
        if (!$title) {
            return null;
        }

        return I18nHelper::translate($title, $this->i18nMap);
    }

    public function getIcon(): ?string
    {
        $icon = $this->getConfig('icon');
        if (!$icon) {
            return null;
        }

        return lx::$app->conductor->getRelativePath($icon, lx::$app->sitePath);
    }

	public function getRespondent(string $name): ?Respondent
	{
		return $this->conductor->getRespondent($name);
	}

	/**
	 * This method is used by ResourceContext for return Plugin as resource
	 */
	public function run(array $params = [], ?UserInterface $user = null): ResponseInterface
	{
		$builder = new PluginBuildContext(['plugin' => $this]);
		$result = $builder->build();

		return $this->prepareResponse($result);
	}

	/**
	 * Define in child
	 */
	protected function handleAjaxResponse(array $data): ResponseInterface
	{
		return $this->prepareErrorResponse('Resource not found', ResponseCodeEnum::NOT_FOUND);
	}

	public function getCacheInfo(): array
    {
        return [
            'type' => $this->getConfig('cacheType'),
            'exists' => $this->cacheExists(),
        ];
    }

    public function cacheExists(): bool
    {
        $dir = new Directory($this->conductor->getSnippetsCachePath());
        return $dir->exists();
    }

    public function buildCache(): void
    {
        if (!$this->cacheExists()) {
            $this->renewCache();
        }
    }

	public function renewCache(): void
	{
		$builder = new PluginBuildContext(['plugin' => $this]);
		$builder->buildCache();
	}

	public function dropCache(): void
	{
		$dir = new Directory($this->conductor->getSnippetsCachePath());
		$dir->remove();
	}

	public function beforeAddAttributes(array $attributes): array
	{
		return $attributes;
	}

	public function afterAddAttributes(array $attributes): void
	{
		// pass
	}

	public function beforeCompile(): void
	{
		// pass
	}

	public function afterCompile(): void
	{
		// pass
	}
	
	/**
	 * @param mixed $value
	 */
	public function setConfig(string $name, $value): void
	{
		$this->config[$name] = $value;
	}

	public function setAnchor(string $anchor): void
	{
		$this->anchor = $anchor;
	}

	public function getAnchor(): string
	{
		return $this->anchor;
	}

	public function setRootSnippetKey(string $key): void
	{
		$this->rootSnippetKey = $key;
	}

	public function getRootSnippetKey(): string
	{
		return $this->rootSnippetKey;
	}

	public function getResourceContext(string $respondent, array $data): ?ResourceContext
	{
		if (!isset($data['attributes']) || !isset($data['data'])) {
			lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
				'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
				'msg' => "Wrong data in ajax-request for plugin '{$this->name}'",
			]);
			return null;
		}

		$this->attributes->setProperties($data['attributes']);
		$requestData = $data['data'];

		if ($respondent) {
			return $this->ajaxResponseByRespondent($respondent, $requestData);
		}

		return new ResourceContext([
			'object' => $this,
			'method' => self::AJAX_RESOURCE_METHOD,
			'params' => [$requestData],
		]);
	}

	public function addAttributes(array $attributes): void
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
	 * @param mixed $value
	 */
	public function addAttribute(string $name, $value): void
	{
		$this->attributes->$name = $value;
	}

	/**
	 * @param array|string $config
	 */
	public function addScript($config): void
	{
		$asset = new JsScriptAsset($this, $config);
		$path = $asset->getPath();
		if ($path && !array_key_exists($path, $this->scripts)) {
			$this->scripts[$path] = $asset;
		}
	}

	public function addCss(string $path): void
	{
		$path = $this->conductor->getAssetPath($path);
		if ($path) {
			$this->css[$path] = true;
		}
	}

	public function onLoad(string $code): void
	{
		$this->onLoadList[] = $code;
	}

	public function setDependencies(array $list): void
	{
		$this->dependencies = $list;
	}

	public function addDependencies(array $dependencies): void
	{
		$this->dependencies = ArrayHelper::mergeRecursiveDistinct(
			$this->dependencies,
			$dependencies
		);
	}

	public function getModuleDependencies(): array
	{
		if (isset($this->dependencies['modules'])) {
			return $this->dependencies['modules'];
		}

		return [];
	}


	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * METHODS FOR BUILDER
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	public function getBuildData(): array
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

	public function applyBuildData(array $data): void
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

	public function getSelfInfo(): array
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

	public function getOriginScripts(): array
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

	public function getOriginCss(): array
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

	public function getOriginImagePathes(): array
	{
		return $this->conductor->getImagePathesInSite();
	}

	public function getScripts(): array
	{
		$list = $this->getOriginScripts();
		$arr = [];
		foreach ($list as $value) {
			$arr[] = $value['path'];
		}

		$linksMap = AssetCompiler::getLinksMap($arr);
		lx::$app->events->trigger(self::EVENT_BEFORE_GET_AUTO_LINKS, [
		    $linksMap['origins'],
            $linksMap['links']
        ]);
		
		foreach ($linksMap['names'] as $key => $name) {
			$list[$key]['path'] = $name;
		}

		return $list;
	}

	public function getCss(): array
	{
		$list = $this->getOriginCss();
		$linksMap = AssetCompiler::getLinksMap($list);
        lx::$app->events->trigger(self::EVENT_BEFORE_GET_AUTO_LINKS, [
            $linksMap['origins'],
            $linksMap['links']
        ]);

		return $linksMap['names'];
	}

	public function getImagePathes(): array
	{
		$list = $this->getOriginImagePathes();
		$linksMap = AssetCompiler::getLinksMap($list);
        lx::$app->events->trigger(self::EVENT_BEFORE_GET_AUTO_LINKS, [
            $linksMap['origins'],
            $linksMap['links']
        ]);

		return $linksMap['names'];
	}


	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * PRIVATE
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	private function ajaxResponseByRespondent(string $respondentName, array $respondentParams): ?ResourceContext
	{
		$respInfo = preg_split('/[^\w\d_]/', $respondentName);
		$respondent = $this->getRespondent($respInfo[0] ?? '');

		if (!$respondent) {
			lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
				'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
				'msg' => "Respondent '$respondentName' is not found",
			]);
			return null;
		}

		$methodName = $respInfo[1] ?? '';
		if (!method_exists($respondent, $methodName)) {
			lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
				'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
				'msg' => "Method '$methodName' for respondent '$respondentName' is not found",
			]);
			return null;
		}

		return new ResourceContext([
			'object' => $respondent,
			'method' => $methodName,
			'params' => $respondentParams,
		]);
	}
}
