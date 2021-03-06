<?php

namespace lx;

use lx;

/**
 * Class Service
 * @package lx
 *
 * @property-read string $name
 * @property-read string $relativePath
 *
 * @property-read PackageDirectory $directory
 * @property-read ServiceConductor $conductor
 * @property-read ServiceRouter $router
 * @property-read I18nServiceMap $i18nMap
 * @property-read ModelManagerInterface|null $modelManager
 */
class Service implements FusionInterface
{
    use ObjectTrait;
	use ApplicationToolTrait;
	use FusionTrait;

	/** @var string $_name - unique service name */
	protected $_name;

	/** @var string $_path - path to the service directory relative to the application root */
	protected $_path;

	/** @var $_config array */
	protected $_config = null;

	/**
	 * Don't use for service creation. Use Service::create() instead
	 * 
	 * Service constructor.
	 * @param array $config
	 */
	public function __construct(array $config)
	{
	    $serviceConfig = $config['serviceConfig'] ?? [];
	    unset($config['serviceConfig']);
	    $this->__objectConstruct($config);

		$this->setName($serviceConfig['name']);

        $this->_config = $serviceConfig;
        $this->initFusionComponents($this->getConfig('components'));
	}

    /**
     * @return array
     */
    public function getDefaultFusionComponents()
    {
        return [
            'directory' => PackageDirectory::class,
            'conductor' => ServiceConductor::class,
            'router' => ServiceRouter::class,
            'i18nMap' => I18nServiceMap::class,
            'modelManager' => ModelManagerInterface::class,
        ];
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
        }

        return $this->__objectGet($name);
    }

	/**
	 * @param string $name
	 * @return bool
	 */
	public static function exists($name)
	{
		return array_key_exists($name, Autoloader::getInstance()->map->packages);
	}

	/**
	 * If service already was created if will be returned
	 *
	 * @param string $name
	 * @return Service|null
	 */
	public static function create($name)
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

		$path = $app->sitePath . '/' . Autoloader::getInstance()->map->packages[$name];
		$dir = new PackageDirectory($path);

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

        $config = ConfigHelper::prepareServiceConfig($name, $config);
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

		$service = lx::$app->diProcessor->create($className, $config); // new $className($config);
		$app->services->register($name, $service);
		return $service;
	}

	/**
	 * @param string $key
	 * @return mixed|null
	 */
	public function getConfig($key = null)
	{
		if ($key === null) {
			return $this->_config;
		}
		
		if (array_key_exists($key, $this->_config)) {
		    return $this->_config[$key];
        }
		
		return null;
	}

	/**
	 * @return string
	 */
	public function getPath()
	{
		return $this->directory->getPath();
	}

    /**
     * @return string
     */
	public function getCategory()
    {
        $packages = lx::$autoloader->map->packages;
        $path = $packages[$this->name];
        $category = (explode('/' . $this->name, $path))[0];
        return $category;
    }

	/**
	 * @param string $name
	 * @return string
	 */
	public function getFilePath($name)
	{
		return $this->conductor->getFullPath($name);
	}

	/**
	 * @param string $name
     * @param string $fileClass
	 * @return BaseFile|null
	 */
	public function getFile($name, $fileClass = null)
	{
		$path = $this->getFilePath($name);
		if ($fileClass) {
		    return lx::$app->diProcessor->create($fileClass, [$path]);
        } else {
            return BaseFile::construct($path);
        }
	}

	/**
	 * @return string
	 */
	public function getJsCoreExtension()
	{
		return '';
	}

	/**
	 * @return string
	 */
	public function getMode()
	{
		return $this->getConfig('mode');
	}

	/**
	 * @param string $mode
	 * @return bool
	 */
	public function isMode($mode)
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

	/**
	 * @todo alias for service name (for frontend masking)
	 *
	 * @return string
	 */
	public function getID()
	{
		return $this->_name;
	}

    /**
     * @param string $name
     * @return bool
     */
	public function hasProcess($name)
    {
        $processesConfig = $this->getConfig('processes') ?? [];
        return array_key_exists($name, $processesConfig);
    }

    /**
     * @param string $name
     * @param int $index
     */
	public function runProcess($name, $index = null)
    {
        $processesConfig = $this->getConfig('processes') ?? [];
        if (!array_key_exists($name, $processesConfig)) {
            \lx::devLog(['_' => [__FILE__,__CLASS__,__METHOD__,__LINE__],
                '__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
                'msg' => "Service process '$name' does not exist",
            ]);
            return;
        }

        $processData = $processesConfig[$name];
        $processClassName = $processData['class'];
        $processConfig = $processData['config'] ?? [];

        if (is_subclass_of($processClassName, AbstractApplication::class)) {
            $appConfig = $this->app->getConfig();
            $processConfig = ArrayHelper::mergeRecursiveDistinct($appConfig, $processConfig, true);
        }

        $processConfig['serviceName'] = $this->name;
        $processConfig['processName'] = $name;
        if ($index !== null) {
            $processConfig['processIndex'] = $index;
        }

//        $supervisor = $this->app->processSupervisor;
//        if (!$supervisor) {
//            return;
//        }

        $async = [];
        if (array_key_exists('out', $processConfig)) {
            $out = $processConfig['out'];
            unset($processConfig['out']);
            $async = [
                'message_log' => $out['message_log'] ?? '/dev/null',
                'error_log' => $out['error_log'] ?? '/dev/null',
            ];
        }

        //TODO $index надо узнавать точно. Если он неизвестен, то не факт, что это 1
        // через processSupervisor можно сделать, но тогда ему надо вести счетчики, и, по хорошему, самому быть
        // запущенным в виде процесса, чтобы избежать дедлока по выдаче индексов
        if (!array_key_exists('logDirectory', $processConfig)) {
            $processConfig['logDirectory'] = '@site/log/process/' . $name . '_' . ($index ?? 1);
        }

        if (empty($async)) {
            $async = [
                'message_log_file' => $processConfig['logDirectory'] . '/_dump.log',
                'error_log_file' => $processConfig['logDirectory'] . '/_error.log',
            ];
        }

        $args = [$processClassName];
        if (!empty($processConfig)) {
            $args[] = json_encode($processConfig);
        }
        
        \lx::exec(
            [
                'executor' => 'php',
                'script' => $this->conductor->getFullPath('@core/../process.php'),
                'args' => $args
            ],
            $async
        );
    }

	/**
	 * @param string $pluginName
	 * @return bool
	 */
	public function pluginExists($pluginName)
	{
		// Dynamic plugins
		$dynamicPlugins = $this->getConfig('dynamicPlugins');
		if ($dynamicPlugins && array_key_exists($pluginName, $dynamicPlugins)) {
			$info = $dynamicPlugins[$pluginName];
			if (is_string($info)) {
				$path = $this->app->getPluginPath($info);
				return ($path !== null);
			}

			if (is_array($info)) {
				if (isset($info['method'])) {
					return method_exists($this, $info['method']);
				}

				$path = $this->app->getPluginPath($info['prototype'] ?? null);
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
	 *
	 * @param string $pluginName
	 * @param array $attributes
	 * @return Plugin|null
	 */
	public function getPlugin($pluginName, $attributes = [])
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
			$path = $this->app->getPluginPath($info['prototype']);
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

	/**
	 * @param string $outerPluginName
	 * @param string $selfPluginName
	 * @return Plugin|null
	 */
	public function includePlugin($outerPluginName, $selfPluginName)
	{
		$path = $this->app->getPluginPath($outerPluginName);
		return Plugin::create($this, $selfPluginName, $path);
	}

	/**
	 * Renew cache in all static plugins
	 */
	public function renewPluginsCache()
	{
		$plugins = $this->getStaticPlugins();
		foreach ($plugins as $plugin) {
			$plugin->renewCache();
		}
	}

	/**
	 * Drop cache in all static plugins
	 */
	public function dropPluginsCache()
	{
		$plugins = $this->getStaticPlugins();
		foreach ($plugins as $plugin) {
			$plugin->dropCache();
		}
	}

	/**
	 * @return array
	 */
	public function getStaticPluginsDataList()
	{
		$plugins = (array)$this->getConfig('plugins');

		$result = [];
		foreach ($plugins as $dirName) {
			$dir = $this->directory->get($dirName);
			if (!$dir) {
				continue;
			}

			$dirs = $dir->getDirs()->toArray();
			foreach ($dirs as $subdir) {
				if (PluginBrowser::checkDirectoryIsPlugin($subdir->getPath())) {
					$result[$subdir->getName()] = $this->conductor->getRelativePath($subdir->getPath());
				}
			}
		}

		return $result;
	}

	/**
	 * @return Plugin[]
	 */
	public function getStaticPlugins()
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
	 * @return array
	 */
	public function getDynamicPluginsDataList()
	{
		$dynamicPlugins = $this->getConfig('dynamicPlugins') ?? [];
		$result = [];
		foreach ($dynamicPlugins as $name => $data) {
			$result[$name] = $data;
		}
		return $result;
	}


	/*******************************************************************************************************************
	 * PRIVATE
	 ******************************************************************************************************************/

	/**
	 * @param string $name
	 */
	private function setName($name)
	{
		$this->_name = $name;
		$this->_path = Autoloader::getInstance()->map->packages[$this->_name];
	}
}
