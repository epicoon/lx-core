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
 * @property-read LoggerInterface $logger
 */
abstract class AbstractApplication extends BaseObject
{
	/** @var string */
	private $id;

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

	/** @var LoggerInterface */
	private $_logger;

	/**
	 * AbstractApplication constructor.
	 */
	public function __construct()
	{
		$this->id = Math::randHash();

		$this->_sitePath = \lx::$conductor->sitePath;
		$this->_conductor = new ApplicationConductor();
		$aliases = $this->getConfig('aliases');
		if (!$aliases) $aliases = [];
		$this->_conductor->setAliases($aliases);

		$this->_services = new ServicesMap();

		$loggerConfig = ClassHelper::prepareConfig($this->getConfig('logger'), ApplicationLogger::class);
		$this->_logger = new $loggerConfig['class']($loggerConfig['params']);

		\lx::$app = $this;
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
			case 'logger':
				return $this->_logger;
		}

		return parent::__get($name);
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
	 * @param string|array $data
	 * @param string $locationInfo
	 */
	public function log($data, $locationInfo = null)
	{
		$this->logger->log($data, $locationInfo);
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
		if ($this->_config === null) {
			$this->renewConfig();
		}

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
	 * @param array $params
	 * @param string $onload
	 * @return Plugin|null
	 */
	public function getPlugin($fullPluginName, $params = [], $onload = '')
	{
		if (is_array($fullPluginName)) {
			return $this->getPlugin(
				$fullPluginName['name'] ?? $fullPluginName['plugin'] ?? '',
				$fullPluginName['params'] ?? [],
				$fullPluginName['onload'] ?? ''
			);
		}

		$arr = explode(':', $fullPluginName);
		if (count($arr) != 2) return null;
		$serviceName = $arr[0];
		$pluginName = $arr[1];

		$service = $this->getService($serviceName);
		if (!$service) return null;

		$plugin = $service->getPlugin($pluginName);
		if (!empty($params)) {
			$plugin->addParams($params);
		}

		if ($onload != '') {
			$plugin->onload($onload);
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
