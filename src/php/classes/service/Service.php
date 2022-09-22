<?php

namespace lx;

use lx;

/**
 * @property-read string $name
 * @property-read string $relativePath
 * @property-read ServiceDirectory $directory
 * @property-read ServiceConductor $conductor
 * @property-read ServiceRouter $router
 * @property-read ServiceI18nMap $i18nMap
 * @property-read DbConnectorInterface|null $dbConnector
 * @property-read ModelManagerInterface|null $modelManager
 * @property-read JsModuleInjectorInterface $moduleInjector
 */
class Service implements ObjectInterface, FusionInterface
{
    use ObjectTrait;
	use FusionTrait;

	protected string $_name;
	protected string $_path;
	protected ?array $_config = null;

    protected function beforeObjectConstruct(iterable $config): void
    {
        $serviceConfig = $config['serviceConfig'] ?? [];
        $this->setName($serviceConfig['name']);
        $this->_config = $serviceConfig;
    }

    protected function init(): void
    {
        $this->initFusionComponents($this->getConfig('components') ?? []);
    }

    public function getFusionComponentTypes(): array
    {
        return [
            'dbConnector' => DbConnectorInterface::class,
            'modelManager' => ModelManagerInterface::class,
            'router' => ServiceRouter::class,
            'i18nMap' => ServiceI18nMap::class,
            'moduleInjector' => JsModuleInjectorInterface::class,
        ];
    }

    public function getDefaultFusionComponents(): array
    {
        return [
            'router' => ServiceRouter::class,
            'i18nMap' => ServiceI18nMap::class,
            'moduleInjector' => ServiceJsModuleInjector::class,
        ];
    }

    public static function getDependenciesConfig(): array
    {
        return [
            'directory' => [
                'class' => ServiceDirectory::class,
                'readable' => true,
            ],
            'conductor' => [
                'class' => ServiceConductor::class,
                'readable' => true,
            ],
        ];
    }

    protected function initDependency(string $name, $value): void
    {
        switch ($name) {
            case 'directory':
            case 'conductor':
                $value->setService($this);
                break;
        }
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
        }

        return $this->__objectGet($name);
    }

	public static function exists(string $name): bool
	{
		return array_key_exists($name, Autoloader::getInstance()->map->services);
	}

	/**
	 * If service already was created it will be returned
	 */
	public static function create(string $name): ?Service
	{
		if (!$name) {
			\lx::devLog(['_' => [__FILE__, __CLASS__, __METHOD__, __LINE__],
				'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT & DEBUG_BACKTRACE_IGNORE_ARGS),
				'msg' => "Service name is missed",
			]);
			return null;
		}

		$app = \lx::$app;

		if ($app->services->has($name)) {
			return $app->services->get($name);
		}

		if (!self::exists($name)) {
			\lx::devLog(['_' => [__FILE__, __CLASS__, __METHOD__, __LINE__],
				'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT & DEBUG_BACKTRACE_IGNORE_ARGS),
				'msg' => "Package '$name' not found. Try to reset autoload map",
			]);
			return null;
		}

		$path = $app->sitePath . '/' . Autoloader::getInstance()->map->services[$name];
		$dir = new ServiceDirectory($path);

		$configFile = $dir->getConfigFile();
		if (!$configFile) {
			\lx::devLog(['_' => [__FILE__, __CLASS__, __METHOD__, __LINE__],
				'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT & DEBUG_BACKTRACE_IGNORE_ARGS),
				'msg' => "Config file not found for package '$name'",
			]);
			return null;
		}

		$config = $configFile->get();

		if (!isset($config['service'])) {
			\lx::devLog(['_' => [__FILE__, __CLASS__, __METHOD__, __LINE__],
				'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT & DEBUG_BACKTRACE_IGNORE_ARGS),
				'msg' => "Package '$name' is not a service",
			]);
			return null;
		}

        $config = ConfigHelper::prepareServiceConfig($config, $name);
        $className = $config['class'] ?? self::class;

		if (!ClassHelper::exists($className)) {
			\lx::devLog(['_' => [__FILE__, __CLASS__, __METHOD__, __LINE__],
				'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT & DEBUG_BACKTRACE_IGNORE_ARGS),
				'msg' => "Class '$className' for service '$name' does not exist",
			]);
			return null;
		}

		unset($config['class']);
		$config['name'] = $name;
		$config = ['serviceConfig' => $config];

		$service = lx::$app->diProcessor->create($className, [$config]);
		$app->services->register($name, $service);
		return $service;
	}

	/**
	 * @return mixed
	 */
	public function getConfig(?string $key = null)
	{
		if ($key === null) {
			return $this->_config;
		}
		
		if (array_key_exists($key, $this->_config)) {
		    return $this->_config[$key];
        }
		
		return null;
	}

	public function getPath(): string
	{
		return $this->directory->getPath();
	}

	public function getCategory(): string
    {
        $services = lx::$autoloader->map->services;
        $path = $services[$this->name];
        $category = (explode('/' . $this->name, $path))[0];
        return $category;
    }

	public function getFilePath(string $name): string
	{
		return $this->conductor->getFullPath($name);
	}

	public function getFile(string $name, ?string $fileClass = null): ?BaseFile
	{
		$path = $this->getFilePath($name);
		if ($fileClass) {
		    return lx::$app->diProcessor->create($fileClass, [$path]);
        } else {
            return BaseFile::construct($path);
        }
	}

	public function getMode(): string
	{
		return $this->getConfig('mode');
	}

	public function isMode(string $mode): bool
	{
		$currentMode = $this->getMode();
		if (!$currentMode) return true;

		if (is_array($mode)) {
			foreach ($mode as $value) {
				if ($value == $currentMode) {
					return true;
				}
			}
			return false;
		}

		return $mode == $currentMode;
	}

	public function getID(): string
	{
		return $this->_name;
	}

	public function pluginExists(string $pluginName): bool
	{
		// Dynamic plugins
		$dynamicPlugins = $this->getConfig('dynamicPlugins');
		if ($dynamicPlugins && array_key_exists($pluginName, $dynamicPlugins)) {
			$info = $dynamicPlugins[$pluginName];
			if (is_string($info)) {
				$path = lx::$app->getPluginPath($info);
				return ($path !== null);
			}

			if (is_array($info)) {
				if (isset($info['method'])) {
					return method_exists($this, $info['method']);
				}

				$path = lx::$app->getPluginPath($info['prototype'] ?? null);
				return ($path !== null);
			}

			return false;
		}

		// Static plugins
		$pluginPath = $this->conductor->getPluginPath($pluginName);
		return $pluginPath !== null;
	}

	/**
	 * Dynamic plugins map configuration example:
	 * dynamicPlugins:
	 *   pluginName1:
	 *     method: someMethod  #This service method have to return a plugin
	 *   pluginName2:
	 *     prototype: outer/service:pluginName
	 *     params:
	 *       paramA: value1
	 *       paramB: value2
	 */
	public function getPlugin(string $pluginName, array $attributes = []): ?Plugin
	{
		// Dynamic plugins
		$dynamicPlugins = $this->getConfig('dynamicPlugins');
		if ($dynamicPlugins && array_key_exists($pluginName, $dynamicPlugins)) {
			$info = $dynamicPlugins[$pluginName];
			if (is_string($info)) {
				$info = ['prototype' => $info];
			}
			if (!is_array($info)) {
				return null;
			}

			if (isset($info['method'])) {
				if (!method_exists($this, $info['method'])) {
					return null;
				}
				$result = $this->{$info['method']}();
				if (!($result instanceof Plugin)) {
					return null;
				}
				return $result;
			}

			if (!isset($info['prototype'])) {
				return null;
			}
			$path = lx::$app->getPluginPath($info['prototype']);
			if (!$path) {
				return null;
			}

			$plugin = Plugin::create($this, $pluginName, $path, $info['prototype']);
			if (isset($info['attributes'])) {
				$plugin->addAttributes($info['attributes']);
			}
			$plugin->addAttributes($attributes);
			return $plugin;
		}

		// Static plugins
		$pluginPath = $this->conductor->getPluginPath($pluginName);
		if ($pluginPath === null) {
			return null;
		}
		$plugin = Plugin::create($this, $pluginName, $pluginPath);
		$plugin->addAttributes($attributes);
		return $plugin;
	}

	public function includePlugin(string $outerPluginName, string $selfPluginName): ?Plugin
	{
		$path = lx::$app->getPluginPath($outerPluginName);
		return Plugin::create($this, $selfPluginName, $path);
	}

	public function renewPluginsCache(): void
	{
		$plugins = $this->getStaticPlugins();
		foreach ($plugins as $plugin) {
            (new PluginCacheManager($plugin))->renewCache();
		}
	}

	public function dropPluginsCache(): void
	{
		$plugins = $this->getStaticPlugins();
		foreach ($plugins as $plugin) {
            (new PluginCacheManager($plugin))->dropCache();
		}
	}

	/**
	 * @return array<string> keys - directories with groups of plugins
	 */
	public function getStaticPluginsDataList(): array
	{
		$plugins = (array)$this->getConfig('plugins');

		$result = [];
		foreach ($plugins as $dirName) {
			$dir = $this->directory->get($dirName);
			if (!$dir) {
				continue;
			}

			$dirs = $dir->getDirectories()->toArray();
			foreach ($dirs as $subdir) {
				if (PluginBrowser::checkDirectoryIsPlugin($subdir->getPath())) {
					$result[$subdir->getName()] = $this->conductor->getRelativePath($subdir->getPath());
				}
			}
		}

		return $result;
	}

	/**
	 * @return array<Plugin>
	 */
	public function getStaticPlugins(): array
	{
		$list = $this->getStaticPluginsDataList();
		$result = [];
		foreach ($list as $name => $path) {
			$plugin = Plugin::create($this, $name, $this->getPath() . '/' . $path);
			if ($plugin) {
				$result[$name] = $plugin;
			}
		}
		return $result;
	}

	/**
	 * @return array<array|string>
	 */
	public function getDynamicPluginsDataList(): array
	{
		$dynamicPlugins = $this->getConfig('dynamicPlugins') ?? [];
		$result = [];
		foreach ($dynamicPlugins as $name => $data) {
			$result[$name] = $data;
		}
		return $result;
	}


	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * PRIVATE
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	private function setName(string $name): void
	{
		$this->_name = $name;
		$this->_path = Autoloader::getInstance()->map->services[$this->_name];
	}
}
