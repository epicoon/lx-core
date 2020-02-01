<?php

namespace lx;

/**
 * Class AbstractApplication
 * @package lx
 *
 * @property string $sitePath
 * @property Directory $directory
 * @property ApplicationConductor $conductor
 * @property ServicesMap $services
 * @property LoggerInterface $logger
 */
abstract class AbstractApplication
{
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
		$this->_logger = new $loggerConfig['class']($this, $loggerConfig['params']);

		\lx::$app = $this;
	}

	/**
	 * @param $name string
	 * @return mixed
	 */
	public function __get($name)
	{
		switch ($name) {
			case 'sitePath': return $this->_sitePath;
			case 'directory': return new Directory($this->_sitePath);
			case 'conductor': return $this->_conductor;
			case 'services': return $this->_services;
			case 'logger': return $this->_logger;
		}

		return null;
	}

	/**
	 * @param $data string|array
	 * @param $locationInfo string|null
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
	 * Получить конфиги приложения, или конкретный конфиг
	 *
	 * @param $param string|null
	 * @return mixed
	 */
	public function getConfig($param = null)
	{
		if ($this->_config === null) {
			$this->loadConfig();
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
				(new File($this->conductor->getSystemPath('defaultServiceConfig')))->load();
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
				(new File($this->conductor->getSystemPath('defaultPluginConfig')))->load();
		}

		return $this->defaultPluginConfig;
	}

	/**
	 * Проверяет текущий режим работы приложения
	 * Если режим не установлен - разрешен любой
	 *
	 * @param $mode string
	 * @return bool
	 */
	public function isMode($mode)
	{
		$currentMode = $this->getConfig('mode');
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
	 * @param $name string
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
	 * @param $file string|File
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
	 * @param $name string
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
	 * Получение плагина
	 *
	 * @param $fullPluginName string
	 * @param $renderParams array
	 * @param $clientParams array
	 * @return Plugin|null
	 */
	public function getPlugin($fullPluginName, $renderParams = [], $clientParams = [])
	{
		if (is_array($fullPluginName)) {
			return $this->getPlugin(
				$fullPluginName['name'] ?? $fullPluginName['plugin'] ?? '',
				$fullPluginName['renderParams'] ?? [],
				$fullPluginName['clientParams'] ?? []
			);
		}

		$arr = explode(':', $fullPluginName);
		if (count($arr) != 2) return null;
		$serviceName = $arr[0];
		$pluginName = $arr[1];

		$service = $this->getService($serviceName);
		if (!$service) return null;

		$plugin = $service->getPlugin($pluginName);
		if (!empty($renderParams)) {
			$plugin->addRenderParams($renderParams);
		}
		if (!empty($clientParams)) {
			$plugin->clientParams->setProperties($clientParams);
		}
		return $plugin;
	}

	/**
	 * Получение пути к модулю
	 *
	 * @param $serviceName string
	 * @param $pluginName string|null
	 * @return string
	 */
	public function getPluginPath($serviceName, $pluginName = null)
	{
		if ($pluginName === null) {
			$arr = explode(':', $serviceName);
			$serviceName = $arr[0];
			$pluginName = $arr[1];
		}

		return $this->getService($serviceName)->conductor->getPluginPath($pluginName);
	}

	/**
	 * Получение менеджера модели из сервиса
	 *
	 * @param $serviceName string
	 * @param $modelName string|null
	 * @return mixed|null  TODO mixed - временно, нужен интерфейс
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
		if ( ! $service) {
			return null;
		}

		return $service->getModelManager($modelName);
	}

	abstract public function run();

	/**
	 * Перезагрузка основных конфигов приложения
	 */
	public function renewConfig()
	{
		$path = $this->_conductor->appConfig;
		if (!$path) {
			$this->_config = [];
		} else {
			$this->_config = require($path);
		}
	}

	/**
	 * Загрузка основных конфигов приложения
	 */
	private function loadConfig()
	{
		$path = $this->_conductor->appConfig;
		if (!$path) {
			$this->_config = [];
		} else {
			$this->_config = require($path);
		}
	}
}
