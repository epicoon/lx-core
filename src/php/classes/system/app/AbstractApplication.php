<?php

namespace lx;

/**
 * Class AbstractApplication
 * @package lx
 *
 * @property $sitePath string
 * @property $directory Directory
 * @property $conductor ApplicationConductor
 * @property $services ServicesMap
 * @property $logger LoggerInterface
 */
abstract class AbstractApplication {
	private $_sitePath;
	private $_config;
	private $_conductor;
	private $_logger;

	private $_services;
	private $defaultServiceConfig;
	private $defaultPluginConfig;

	public function __construct() {
		$this->_sitePath = \lx::$conductor->sitePath;
		$this->_conductor = new ApplicationConductor($this);
		$aliases = $this->getConfig('aliases');
		if (!$aliases) $aliases = [];
		$this->_conductor->setAliases($aliases);

		$this->_services = new ServicesMap($this);

		$loggerConfig = ClassHelper::prepareConfig($this->getConfig('logger'), ApplicationLogger::class);
		$loggerConfig['params']['app'] = $this;
		$this->_logger = new $loggerConfig['class']($loggerConfig['params']);

		\lx::$app = $this;
	}

	public function __get($name) {
		switch ($name) {
			case 'sitePath': return $this->_sitePath;
			case 'directory': return new Directory($this->_sitePath);
			case 'conductor': return $this->_conductor;
			case 'services': return $this->_services;
			case 'logger': return $this->_logger;
		}

		return null;
	}
	
	public function log($data, $locationInfo = null) {
		$this->logger->log($data, $locationInfo);
	}

	/**
	 * Получить конфиги приложения, или конкретный конфиг
	 */
	public function getConfig($param = null) {
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
	 *
	 * */
	public function getDefaultServiceConfig() {
		if ($this->defaultServiceConfig === null) {
			$this->defaultServiceConfig =
				(new File($this->conductor->getSystemPath('defaultServiceConfig')))->load();
		}

		return $this->defaultServiceConfig;
	}

	/**
	 *
	 * */
	public function getDefaultPluginConfig() {
		if ($this->defaultPluginConfig === null) {
			$this->defaultPluginConfig =
				(new File($this->conductor->getSystemPath('defaultPluginConfig')))->load();
		}

		return $this->defaultPluginConfig;
	}

	/**
	 * Проверяет текущий режим работы приложения
	 * Если режим не установлен - разрешен любой
	 */
	public function isMode($mode) {
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
	 * //todo - надо добавить псевдонимы для сервисов?
	 * */
	public function getService($name) {
		if (Service::exists($name)) {
			return $this->services->get($name);
		}

		return null;
	}

	/**
	 *
	 * */
	public function getPackagePath($name) {
		$map = Autoloader::getInstance()->map;
		if (!array_key_exists($name, $map->packages)) {
			return false;
		}

		$path = $map->packages[$name];
		return $this->conductor->getFullPath($path);
	}

	/**
	 * Получение модуля из сервиса
	 * */
	public function getPlugin($serviceName, $pluginName = null) {
		if ($pluginName === null) {
			$arr = explode(':', $serviceName);
			$serviceName = $arr[0];
			$pluginName = $arr[1];
		}

		return $this->getService($serviceName)->getPlugin($pluginName);
	}

	/**
	 * Получение пути к модулю
	 * */
	public function getPluginPath($serviceName, $pluginName = null) {
		if ($pluginName === null) {
			$arr = explode(':', $serviceName);
			$serviceName = $arr[0];
			$pluginName = $arr[1];
		}

		return $this->getService($serviceName)->conductor->getPluginPath($pluginName);
	}

	/**
	 * Получение менеджера модели из сервиса
	 * */
	public function getModelManager($serviceName, $modelName = null) {
		if ($modelName === null) {
			$arr = explode('.', $serviceName);
			$serviceName = $arr[0];
			$modelName = $arr[1];
		}

		return $this->getService($serviceName)->getModelManager($modelName);
	}

	abstract public function run();

	/**
	 * Загрузка основных конфигов приложения
	 */
	private function loadConfig() {
		$path = $this->_conductor->appConfig;
		if (!$path) {
			$this->_config = [];
		} else {
			$this->_config = require($path);
		}
	}
}
