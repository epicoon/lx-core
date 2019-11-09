<?php

namespace lx;

class Service extends ApplicationTool implements FusionInterface {
	use FusionTrait;

	/** @var $_name string - уникальное имя сервиса */
	protected $_name;
	/** @var $_path string - путь к каталогу сервиса относительно корня приложения */
	protected $_path;
	/** @var $_config array - массив настроек сервиса */
	protected $_config = null;
	/** @var $_dir lx\PackageDirectory - объект, воплощающий директорию сервиса */
	protected $_dir = null;
	/** @var $_conductor lx\Conductor - проводник по структуре сервиса */
	protected $_conductor = null;

	/** @var $dbConnections array - соединения с базами банных */
	private $dbConnections = [];

	/**
	 * Не использовать для создания экземпляров сервисов
	 * */
	public function __construct($app, $name, $config, $params = []) {
		parent::__construct($app);
		$this->setName($name);
		$this->setConfig($config);
		$this->construct($params);
	}

	protected function construct($params) {

	}

	/**
	 * 
	 * */
	private function setName($name) {
		$this->_name = $name;
		$this->_path = Autoloader::getInstance()->map->packages[$this->_name];
	}

	/**
	 * 
	 * */
	private function setConfig($config) {
		// Общие настройки
		$commonConfig = $this->app->getDefaultServiceConfig();
		ConfigHelper::prepareServiceConfig($commonConfig, $config);

		// Инъекция настроек
		$injections = $this->app->getConfig('configInjection');
		ConfigHelper::serviceInject($this->_name, $injections, $config);

		$config['service']['dbList'] = $this->getDbConfig($config);
		unset($config['service']['db']);

		$this->_config = $config;
		$this->initFusionComponents($this->getConfig('service.components'));
	}

	/**
	 * @param $name
	 * @return bool
	 */
	public static function exists($name) {
		return array_key_exists($name, Autoloader::getInstance()->map->packages);
	}

	/**
	 * Проверяет карту сервисов - если такой сервис уже создавался, он будет взят оттуда
	 * Создание нового сервиса приведет к записи в карту сервисов
	 * Сервис создается с учетом класса, установленного основным для данного сервиса
	 * */
	public static function create($app, $name) {
		if (!$name) {
			throw new \Exception("Service name is missed", 400);
		}

		if ($app->services->has($name)) {
			return $app->services->get($name);
		}

		if (!array_key_exists($name, Autoloader::getInstance()->map->packages)) {
			throw new \Exception("Package '$name' not found. Try to reset autoload map", 400);
		}

		$path = $app->sitePath . '/' . Autoloader::getInstance()->map->packages[$name];
		$dir = new PackageDirectory($path);

		$configFile = $dir->getConfigFile();
		if (!$configFile) {
			throw new \Exception("Config file not found for package '$name'", 400);
		}

		$config = $configFile->get();

		if (!isset($config['service'])) {
			throw new \Exception("Package '$name' is not a service", 400);
		}

		if (isset($config['service']['class'])) {
			$data = ClassHelper::prepareConfig($config['service']['class'], self::class);	
			$className = $data['class'];
			$params = $data['params'];
		} else {
			$className = self::class;
			$params = [];
		}

		if (!ClassHelper::exists($className)) {
			throw new \Exception("Class '$className' for service '$name' does not exist", 400);			
		}

		$service = new $className($app, $name, $config, $params);
		$app->services->register($name, $service);
		return $service;
	}

	/**
	 *
	 * */
	public function __get($name) {
		switch ($name) {
			case 'name': return $this->_name;

			case 'relativePath': return $this->_path;

			case 'directory':
				if ($this->_dir === null) {
					$this->_dir = new PackageDirectory($this->app->sitePath . '/' . $this->_path);
				}

				return $this->_dir;

			case 'conductor':
				if ($this->_conductor === null) {
					$this->_conductor = new ServiceConductor($this);
				}

				return $this->_conductor;
		}

		$component = $this->getFusionComponent($name);
		if ($component) {
			return $component;
		}

		return parent::__get($name);
	}

	public function getFusionComponentsDefaultConfig()
	{
		return [
			'i18nMap' => I18nServiceMap::class,
		];
	}

	/**
	 * Получить конфигурацию сервиса
	 * @param $key - можно получить конкретную конфигурацию по ключу, необязательный параметр
	 * @return mixed|null
	 * */
	public function getConfig($key = null) {
		if ($key === null) {
			return $this->_config;
		}

		$keyArr = explode('.', $key);
		$data = $this->_config;
		$result;
		foreach ($keyArr as $value) {
			if (array_key_exists($value, $data)) {
				$data = $data[$value];
			} else {
				return null;
			}
		}

		return $data;
	}

	/**
	 *
	 * */
	public function getPath() {
		return $this->directory->getPath();
	}

	/**
	 *
	 * */
	public function getFilePath($name) {
		return $this->conductor->getFullPath($name);
	}

	/**
	 *
	 * */
	public function getFile($name) {
		$path = $this->getFullPath($name);
		return new BaseFile($path);
	}

	/**
	 *
	 * */
	public function router() {
		$routerData = $this->getConfig('service.router');

		$className = ($routerData !== null && isset($routerData['type']) && $routerData['type'] == 'class' && isset($routerData['name']))
			? $routerData['name']
			: ServiceRouter::class;

		$router = new $className($this);

		return $router;
	}

	/**
	 *
	 * */
	public function getModelManager($modelName) {
		if ( ! $this->modelProvider) {
			return null;
		}

		return $this->modelProvider->getManager($modelName);
	}

	/**
	 *
	 * */
	public function getMode() {
		return $this->getConfig('service.mode');
	}

	/**
 	 * Проверяет текущий режим работы сервиса
 	 * Если режим не установлен - разрешен любой
	 * */
	public function isMode($mode) {
		$currentMode = self::getConfig('mode');
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
	 * Уникальный идентификатор сервиса - по умолчанию это его имя
	 * */
	public function getID() {
		//todo добавить возможность делать сервисам псевдонимы
		return $this->_name;
	}

	/**
	 *
	 * */
	public function pluginExists($pluginName) {
		$dynamicPlugins = $this->getConfig('service.dynamicPlugins');
		if ($dynamicPlugins && array_key_exists($pluginName, $dynamicPlugins)) {
			$info = $dynamicPlugins[$pluginName];
			if (is_array($info)) {
				if (isset($info['method'])) {
					return method_exists($this, $info['method']);
				}

				return isset($info['prototype']);
			} else {
				//todo - если не массив?
				return false;
			}
		}

		// Поиск статических модулей
		$pluginPath = $this->conductor->getPluginPath($pluginName);
		return $pluginPath !== null;
	}

	/**
	 *
	 * */
	public function getPlugin($pluginName, $argRenderParams = []) {
		// Карта динамических модулей
		/*
		dynamicPlugins:
		  pluginName1:
		    method: someMethod  #Метод сервиса, возвращающий этот модуль
		  pluginName2:
		    plugin: outer/service:pluginName
		    renderParams:
		      method: someMethodForParams  #Метод сервиса, возвращающий массив параметров для модуля
		      paramA: value
		      paramB: ()=>$this->someField1 . ';' . $this->someField2;
		*/
		$dynamicPlugins = $this->getConfig('service.dynamicPlugins');
		if ($dynamicPlugins && array_key_exists($pluginName, $dynamicPlugins)) {
			$info = $dynamicPlugins[$pluginName];
			if (is_array($info)) {
				if (isset($info['method'])) {
					if (!method_exists($this, $info['method'])) {
						return null;
					}
					return $this->{$info['method']}();
				}

				if (!isset($info['prototype'])) {
					return null;
				}
				$path = $this->app->getPluginPath($info['prototype']);
				$plugin = Plugin::create($this, $pluginName, $path, $info['prototype']);
				if (isset($info['renderParams'])) {
					$renderParams = $info['renderParams'];
					if (isset($renderParams['method'])) {
						if (method_exists($this, $renderParams['method'])) {
							$renderParams = $this->{$renderParams['method']}();
						}
					}
					foreach ($renderParams as $key => $value) {
						if (preg_match('/^\(\)=>/', $value)) {
							$renderParams[$key] = eval(preg_replace('/^\(\)=>/', 'return ', $value));
						}
					}
					$plugin->addRenderParams($renderParams);
				}
				$plugin->addRenderParams($argRenderParams);
				return $plugin;
			} else {
				//todo - если не массив?
				return null;
			}
		}

		// Поиск статических модулей
		$pluginPath = $this->conductor->getPluginPath($pluginName);
		if ($pluginPath === null) {
			return null;
		}
		$plugin = Plugin::create($this, $pluginName, $pluginPath);
		$plugin->addRenderParams($argRenderParams);
		return $plugin;
	}

	/**
	 *
	 * */
	public function includePlugin($outerPluginName, $selfPluginName) {
		$path = $this->app->getPluginPath($outerPluginName);
		return Plugin::create($this, $selfPluginName, $path);
	}

	/**
	 *
	 * */
	public function db($db = 'db') {
		if (!array_key_exists($db, $this->dbConnections)) {
			$dbList = $this->getConfig('service.dbList');

			if (!isset($dbList[$db])) {
				throw new \Exception("There is no settings for connection service '{$this->name}' with DB '$db'", 400);
			}

			$dbConfig = $dbList[$db];
			$connection = DB::create($dbConfig);
			if ( ! $connection) {
				return null;
			}

			$connection->connect();
			$this->dbConnections[$db] = $connection;
		}
		return $this->dbConnections[$db];
	}

	/**
	 *
	 * */
	public function closeDbConnection($db = 'db') {
		if (isset($this->dbConnections[$db])) {
			$this->dbConnections[$db]->close();
			unset($this->dbConnections[$db]);
		}
	}

	/**
	 * В конфиге может быть dbList - это ассоциативный массив настроек для подключений, ключи используются для выбора нужного подключения
	 * В конфиге может быть db - это настройки для одного подключения (ключ 'db' используется по умочанию)
	 * */
	protected function getDbConfig($config) {
		$dbList = [];
		if (isset($config['service']['db'])) {
			$dbList['db'] = $config['service']['db'];
		}
		if (isset($config['service']['dbList'])) {
			$dbList += $config['service']['dbList'];
		}

		$commonConfig = $this->app->getDefaultServiceConfig();
		$commonDbList = [];
		if (isset($commonConfig['db'])) {
			$commonDbList['db'] = $commonConfig['db'];
		}
		if (isset($commonConfig['dbList'])) {
			$commonDbList += $commonConfig['dbList'];
		}

		if (empty($commonDbList)) {
			return $dbList;
		}

		$dbList += $commonDbList;
		return $dbList;
	}
}
