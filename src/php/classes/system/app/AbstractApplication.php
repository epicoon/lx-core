<?php

namespace lx;

/**
 * Class AbstractApplication
 * @package lx
 *
 * @property-read string $sitePath
 * @property-read Directory $directory
 * @property-read ApplicationConductor $conductor
 * @property-read ServicesMap $services
 * @property-read DependencyProcessor $diProcessor
 * @property-read EventManager $events
 * @property-read LoggerInterface $logger
 */
abstract class AbstractApplication implements FusionInterface
{
    use ObjectTrait;
    use FusionTrait;

	/** @var string */
	private $id;

	/** @var integer */
	private $pid;

	/** @var string */
	private $_sitePath;

	/** @var array */
	private $_config;

	/** @var array */
	private $defaultServiceConfig;

	/** @var array */
	private $defaultPluginConfig;

	/** @var ApplicationConductor */
	private $_conductor;

	/** @var ServicesMap */
	private $_services;

	/** @var EventManager */
	private $_events;

	/** @var DependencyProcessor */
	private $_diProcessor;

	/** @var bool */
	private $logMode;

	/**
	 * AbstractApplication constructor.
     * @param array $config
	 */
	public function __construct($config = [])
	{
		$this->id = Math::randHash();
		$this->pid = getmypid();
		$this->_sitePath = \lx::$conductor->sitePath;
		$this->logMode = true;

		$this->_conductor = new ApplicationConductor();
        \lx::$app = $this;

        if (empty($config)) {
            $this->renewConfig();
        } else {
            $this->_config = $config;
        }

		$aliases = $this->getConfig('aliases');
		if (!$aliases) $aliases = [];
		$this->_conductor->setAliases($aliases);

		$this->_services = new ServicesMap();

		$diConfig = ClassHelper::prepareConfig(
            $this->getConfig('diProcessor'),
            DependencyProcessor::class
        );
		$this->_diProcessor = new $diConfig['class']($diConfig['params']);

        $eventsConfig = ClassHelper::prepareConfig(
            $this->getConfig('eventManager'),
            EventManager::class
        );
        $this->_events = new $eventsConfig['class']($eventsConfig['params']);

        $this->initFusionComponents($this->getConfig('components'), static::getDefaultComponents());

        $this->init();
	}

    /**
     * @return array
     */
    protected static function getDefaultComponents()
    {
        return [
            'logger' => ApplicationLogger::class,
        ];
    }

    protected function init()
    {
        // pass
    }

	/**
	 * @param $name string
	 * @return mixed
	 */
	public function __get($name)
	{
		switch ($name) {
			case 'sitePath':
				return $this->_sitePath;
			case 'directory':
				return new Directory($this->_sitePath);
			case 'conductor':
				return $this->_conductor;
			case 'services':
				return $this->_services;
            case 'diProcessor':
                return $this->_diProcessor;
            case 'events':
                return $this->_events;
		}

		return $this->__objectGet($name);
	}

    /**
     * @return int
     */
	public function getPid()
    {
        return $this->pid;
    }

	/**
	 * @param string $alias
	 * @return string
	 */
	public function getFullPath($alias)
	{
		return $this->conductor->getFullPath($alias);
	}

    /**
     * @param bool $value
     */
	public function setLogMode($value)
    {
        $this->logMode = $value;
    }

	/**
	 * @param string|array $data
	 * @param string $locationInfo
	 */
	public function log($data, $locationInfo = null)
	{
	    if ($this->logMode) {
            $this->logger->log($data, $locationInfo);
        }
	}

	/**
	 * @return string
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param string|null $param
	 * @return mixed
	 */
	public function getConfig($param = null)
	{
		if ($param === null) {
			return $this->_config;
		}

		if (array_key_exists($param, $this->_config)) {
			return $this->_config[$param];
		}

		return null;
	}

	/**
	 * @return array
	 */
	public function getDefaultServiceConfig()
	{
		if ($this->defaultServiceConfig === null) {
			$this->defaultServiceConfig =
				(new File(\lx::$conductor->getDefaultServiceConfig()))->load();
		}

		return $this->defaultServiceConfig;
	}

	/**
	 * @return array
	 */
	public function getDefaultPluginConfig()
	{
		if ($this->defaultPluginConfig === null) {
			$this->defaultPluginConfig =
				(new File(\lx::$conductor->getDefaultPluginConfig()))->load();
		}

		return $this->defaultPluginConfig;
	}

	/**
	 * @return string
	 */
	public function getMode()
	{
		return $this->getConfig('mode');
	}

	/**
	 * @param string|array $mode
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
	 * @return bool
	 */
	public function isProd()
	{
		return $this->isMode(\lx::MODE_PROD);
	}

	/**
	 * @return bool
	 */
	public function isNotProd()
	{
		return !$this->isMode(\lx::MODE_PROD);
	}

	/**
	 * @param string $name
	 * @return Service|null
	 */
	public function getService($name)
	{
		if (Service::exists($name)) {
			return $this->services->get($name);
		}

		return null;
	}

	/**
	 * @param string|File $file
	 * @return Service|null
	 */
	public function getServiceByFile($file)
	{
		if (is_string($file)) {
			$filePath = $this->conductor->getFullPath($file);
		} elseif ($file instanceof File) {
			$filePath = $file->getPath();
		} else {
			return null;
		}

		$map = Autoloader::getInstance()->map->packages;
		foreach ($map as $name => $servicePath) {
			$fullServicePath = addcslashes($this->sitePath . '/' . $servicePath, '/');
			if (preg_match('/^' . $fullServicePath . '\//', $filePath)) {
				return $this->getService($name);
			}
		}

		return null;
	}

	/**
	 * @param string $name
	 * @return string|false
	 */
	public function getPackagePath($name)
	{
		$map = Autoloader::getInstance()->map;
		if (!array_key_exists($name, $map->packages)) {
			return false;
		}

		$path = $map->packages[$name];
		return $this->conductor->getFullPath($path);
	}

	/**
	 * @param string $fullPluginName
	 * @param array $attributes
	 * @param string $onLoad
	 * @return Plugin|null
	 */
	public function getPlugin($fullPluginName, $attributes = [], $onLoad = '')
	{
		if (is_array($fullPluginName)) {
			return $this->getPlugin(
				$fullPluginName['name'] ?? $fullPluginName['plugin'] ?? '',
				$fullPluginName['attributes'] ?? [],
				$fullPluginName['onLoad'] ?? ''
			);
		}

		$arr = explode(':', $fullPluginName);
		if (count($arr) != 2) return null;
		$serviceName = $arr[0];
		$pluginName = $arr[1];

		$service = $this->getService($serviceName);
		if (!$service) return null;

		$plugin = $service->getPlugin($pluginName);
		if (!empty($attributes)) {
			$plugin->addAttributes($attributes);
		}

		if ($onLoad != '') {
			$plugin->onLoad($onLoad);
		}
		return $plugin;
	}

	/**
	 * @param string $serviceName
	 * @param string $pluginName
	 * @return string|null
	 */
	public function getPluginPath($serviceName, $pluginName = null)
	{
		if (!$serviceName) {
			return null;
		}

		if ($pluginName === null) {
			$arr = explode(':', $serviceName);
			$serviceName = $arr[0];
			$pluginName = $arr[1];
		}

		return $this->getService($serviceName)->conductor->getPluginPath($pluginName);
	}

	/**
	 * @param string $serviceName
	 * @param string $modelName
	 * @return ModelManagerInterface|null
	 */
	public function getModelManager($serviceName, $modelName = null)
	{
		if ($modelName === null) {
			$arr = explode('.', $serviceName);
			if (count($arr) != 2) {
				return null;
			}

			$serviceName = $arr[0];
			$modelName = $arr[1];
		}

		$service = $this->getService($serviceName);
		if (!$service) {
			return null;
		}

		return $service->getModelManager($modelName);
	}

	/**
	 * @return void
	 */
	abstract public function run();

	/**
	 * Reload application configuration
	 */
	public function renewConfig()
	{
		$path = \lx::$conductor->getAppConfig();
		if (!$path) {
			$this->_config = [];
		} else {
			$this->_config = require($path);
		}
	}
}
