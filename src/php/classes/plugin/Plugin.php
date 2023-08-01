<?php

namespace lx;

use lx;

/**
 * @property-read string $name
 * @property-read string $relativePath
 * @property-read string|null $prototype
 * @property-read PluginDirectory $directory
 * @property-read PluginConductor $conductor
 * @property-read PluginI18nMap $i18nMap
 * @property-read JsModuleInjectorInterface|null $moduleInjector
 */
class Plugin extends Resource implements ObjectInterface, FusionInterface
{
    use ObjectTrait;
	use FusionTrait;

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
    private string $cssPreset;
	private array $dependencies = ['modules' => ['lx.Box']];
	private array $scripts = [];
	private array $css = [];

    protected function beforeObjectConstruct(iterable $config): void
    {
        $this->_path = $config['path'];
    }

    protected function afterObjectConstruct(iterable $config): void
    {
        $this->service = $config['service'];
        $this->_name = $this->service->getID() . ':' . $config['name'];
        $this->attributes = new DataObject();
        $this->anchor = '_root_';

        if (isset($config['prototype'])) {
            $this->_prototype = $config['prototype'];
        }

        $this->config = ConfigHelper::preparePluginConfig($config['config'], $this->name, $this->prototype);
        $this->initFusionComponents($this->getConfig('components') ?? []);
        $this->resetCssPreset();
    }
    
    protected function init(): void
    {
        parent::init();
    }

	/**
	 * Define in child
	 */
	public function widgetBasicCssList(): array
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
        $pluginClass = $config['server'] ?? self::class;
        unset($config['server']);

		$data = [
			'service' => $service,
			'name' => $pluginName,
			'path' => lx::$app->conductor->getRelativePath($pluginPath),
			'config' => $config,
		];

		if ($prototype) {
			$data['prototype'] = $prototype;
		}

		$plugin = lx::$app->diProcessor->create($pluginClass, [$data]);

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

    public function getFusionComponentTypes(): array
    {
        return [
            'i18nMap' => PluginI18nMap::class,
            'moduleInjector' => JsModuleInjectorInterface::class,
        ];
    }

	public function getDefaultFusionComponents(): array
	{
		return [
			'i18nMap' => PluginI18nMap::class,
            'moduleInjector' => PluginJsModuleInjector::class,
		];
	}
    
    public static function getDependenciesConfig(): array
    {
        return array_merge(parent::getDependenciesConfig(), [
            'directory' => [
                'class' => PluginDirectory::class,
                'readable' => true,
            ],
            'conductor' => [
                'class' => PluginConductor::class,
                'readable' => true,
            ],
        ]);
    }

    protected function initDependency(string $name, $value): void
    {
        switch ($name) {
            case 'directory':
            case 'conductor':
                $value->setPlugin($this);
                break;
        }
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

	public function getFile(string $name): ?CommonFileInterface
	{
		return $this->conductor->getFile($name);
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

        return I18nHelper::localizeKey($title, $this->i18nMap);
    }

    public function getIcon(): ?string
    {
        $icon = $this->getConfig('icon');
        if (!$icon) {
            return null;
        }

        return '/' . lx::$app->conductor->getRelativePath($icon, lx::$app->sitePath);
    }

	public function getRespondent(string $name): ?Respondent
	{
		return $this->conductor->getRespondent($name);
	}

    public function resetCssPreset(): void
    {
        $this->cssPreset = lx::$app->cssManager->getDefaultCssPresetName();
    }

    public function setCssPreset($cssPreset): void
    {
        $this->cssPreset = $cssPreset;
    }

    public function getCssPreset(): string
    {
        return $this->cssPreset;
    }

	/**
	 * This method is used by ResourceContext for return Plugin as HttpResponseInterface
     * @see ResourceContext::invokePlugin()
	 */
	public function render(array $params = [], ?UserInterface $user = null): HttpResponseInterface
	{
		$builder = new PluginBuildContext(['plugin' => $this]);
		$result = $builder->build();

		return $this->prepareResponse($result);
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

	public function addDependencies(array $dependencies): void
	{
		$this->dependencies = ArrayHelper::mergeRecursiveDistinct(
			$this->dependencies,
			$dependencies
		);
	}

	public function getModuleDependencies(): array
	{
        return $this->dependencies['modules'] ?? [];
	}


	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * METHODS FOR BUILDER
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    public function getPresetedCssClasses(): array
    {
        $cssDir = new Directory($this->conductor->getLocalSystemPath('css'));
        $presetedFile = $cssDir->get('preseted.json');

        if (!$presetedFile) {
            return [];
        }

        $preseted = $presetedFile->get();
        if ($preseted === 'null') {
            throw new \Exception('Preseted file ' . $presetedFile->getPath() . ' is broken');
        }

        return json_decode($preseted, true);
    }

	public function getBuildData(): array
	{
		$result = [
			'serviceName' => $this->service->name,
			'name' => $this->_name,
			'path' => $this->getPath(),
            'images' => (new PluginAssetProvider($this))->getImagePaths(),
            'cssPreset' => $this->cssPreset,
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

        $preseted = $this->getPresetedCssClasses();
        if (!empty($preseted)) {
            $result['preseted'] = $preseted;
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
	}

    /**
     * @return JsScriptAsset[]
     */
	public function getScriptsList(): array
	{
		$assets = [
			'from-code' => array_values($this->scripts),
			'from-config' => array_values($this->getConfig('scripts-list') ?? []),
		];

		$priopity = $this->getConfig('scripts-priopity') ?? ['from-code', 'from-config'];
		$result = [];
		foreach ($priopity as $key) {
			$result = array_merge($result, $assets[$key]);
		}
		return $result;
	}

	public function getCssList(): array
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

    public function getImagePathsList(): array
    {
        return $this->conductor->getImagePathsInSite();
    }
}
