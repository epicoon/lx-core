<?php

namespace lx;

class Service {
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
	/** @var $_modelProvider lx\ModelProvider - провайдер моделей сервиса */
	protected $_modelProvider = null;

	/** @var $dbConnections array - соединения с базами банных */
	private $dbConnections = [];

	/**
	 * Сервис - картированный вариант синглтона
	 * */
	protected function __construct($name, $config) {
		$this->_name = $name;
		$this->_path = \lx\Autoloader::getInstance()->map->packages[$this->_name];

		// Общие настройки
		$commonConfig = \lx::getDefaultServiceConfig();

		// Дефолтные настройки
		foreach ($commonConfig as $key => $value) {
			if (!array_key_exists($key, $config['service'])) {
				$config['service'][$key] = $commonConfig[$key];
			}
		}

		$config['dbList'] = $this->getDbConfig($config);
		unset($config['db']);
		$this->_config = $config;
	}
	protected function __clone() {}

	/**
	 * Проверяет карту сервисов - если такой сервис уже создавался, он будет взят оттуда
	 * Создание нового сервиса приведет к записи в карту сервисов
	 * Сервис создается с учетом класса, установленного основным для данного сервиса
	 * */
	public static function create($name = null) {
		if ($name === null) {
			$name = ClassHelper::defineService(static::class);
			if ($name === null) {
				throw new \Exception("Service '{static::class}' not found", 400);
			}
		}

		if (ServicesMap::has($name)) {
			return ServicesMap::get($name);
		}

		if (!array_key_exists($name, Autoloader::getInstance()->map->packages)) {
			throw new \Exception("Package '$name' not found. Try to reset autoload map", 400);
		}

		$path = \lx::sitePath() . '/' . Autoloader::getInstance()->map->packages[$name];
		$dir = new PackageDirectory($path);

		$configFile = $dir->getConfigFile();
		if (!$configFile) {
			throw new \Exception("Config file not found for package '$name'", 400);
		}

		$config = $configFile->get();

		if (!isset($config['service'])) {
			throw new \Exception("Package '$name' is not service", 400);
		}

		$className = isset($config['service']['class'])
			? $config['service']['class']
			: self::class;

		if (!ClassHelper::exists($className)) {
			throw new \Exception("Class '$className' for service '$name' does not exist", 400);			
		}

		$service = new $className($name, $config);
		ServicesMap::newService($name, $service);
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
					$this->_dir = new PackageDirectory(\lx::sitePath() . '/' . $this->_path);
				}
				return $this->_dir;
			case 'conductor':
				if ($this->_conductor === null) {
					$this->_conductor = new ServiceConductor($this);
				}
				return $this->_conductor;
			case 'modelProvider':
				if ($this->_modelProvider === null) {
					$crudAdapterClass = $this->getConfig('service.modelCrudAdapter');
					$crudAdapter = $crudAdapterClass === null
						? null
						: new $crudAdapterClass();
					$this->_modelProvider = new ModelProvider($this, $crudAdapter);
				}
				return $this->_modelProvider;
		}

		return null;
	}

	/**
	 * Получить конфигурацию сервиса
	 * @var $key - можно получить конкретную конфигурацию по ключу, необязательный параметр
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
	public function router() {
		$routerData = $this->getConfig('service.router');

		$className = ($routerData !== null && isset($routerData['type']) && $routerData['type'] == 'class' && isset($routerData['name']))
			? $routerData['name']
			: ServiceRouter::class;

		$router = new $className($this);

		return $router;
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
	public function getModule($moduleName, $params = []) {
		// Карта динамических модулей
		/*
		dynamicModules:
		  moduleName1:
		    method: someMethod  #Метод сервиса, возвращающий этот модуль
		  moduleName2:
		    module: outer/service:moduleName
		    params:
		      method: someMethodForParams  #Метод сервиса, возвращающий массив параметров для модуля
		      paramA: value
		      paramB: ()=>$this->someField1 . ';' . $this->someField2;
		*/
		$dynamicModules = $this->getConfig('service.dynamicModules');
		if ($dynamicModules && array_key_exists($moduleName, $dynamicModules)) {
			$info = $dynamicModules[$moduleName];
			if (is_array($info)) {
				if (isset($info['method'])) {
					if (!method_exists($this, $info['method'])) {
						throw new \Exception("Module '$moduleName' not found", 400);
					}
					return $this->{$info['method']}();
				}

				if (!isset($info['prototype'])) {
					throw new \Exception("Module '$moduleName' not found", 400);
				}
				$path = \lx::getModulePath($info['prototype']);
				$module = Module::create($this, $moduleName, $path);
				if (isset($info['params'])) {
					$configParams = $info['params'];
					if (isset($configParams['method'])) {
						if (method_exists($this, $configParams['method'])) {
							$configParams = $this->{$configParams['method']}();
						}
					}
					foreach ($configParams as $key => $value) {
						if (preg_match('/^\(\)=>/', $value)) {
							$configParams[$key] = eval(preg_replace('/^\(\)=>/', 'return ', $value));
						}
					}
					$module->addParams($configParams);
				}
				$module->addParams($params);
				return $module;
			} else {
				//todo - если не массив?
				throw new \Exception("Module '$moduleName' not found", 400);
			}
		}

		// Поиск статических модулей
		$modulePath = $this->conductor->getModulePath($moduleName);
		if ($modulePath === null) {
			throw new \Exception("Module '$moduleName' not found", 400);
		}
		$module = Module::create($this, $moduleName, $modulePath);
		$module->addParams($params);
		return $module;
	}

	/**
	 *
	 * */
	public function includeModule($outerModuleName, $selfModuleName) {
		$path = \lx::getModulePath($outerModuleName);
		return Module::create($this, $selfModuleName, $path);
	}

	/**
	 *
	 * */
	public function db($db = 'db') {
		if (!array_key_exists($db, $this->dbConnections)) {
			$dbList = $this->getConfig('dbList');

			if (!isset($dbList[$db])) {
				throw new \Exception("There is no settings for connection service '{$this->name}' with DB '$db'", 400);
			}

			$dbConfig = $dbList[$db];
			$connection = DB::create($dbConfig);
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
	 *
	 * */
	protected function getDbConfig($config) {
		/*
		В конфиге может быть dbList
			- если это ассоциативный массив, то это подключения к базе - собственные настройки сервиса
			- если это перечислимый массив, то это ключи настроек подключений, которые нужны сервису - нужно их определить в общем конфиге
		В конфиге может быть db
			- это настройки для одного подключения
		*/
		$dbList = [];
		if (isset($config['db'])) {
			$dbList['db'] = $config['db'];
		}
		if (isset($config['dbList'])) {
			$dbList += $config['dbList'];
		}

		$lxDb = \lx::getConfig('db');
		if (!$lxDb) return $dbList;

		if (isset($lxDb['dbMap'][$this->name])) {
			$dbList += $lxDb['dbMap'][$this->name];
		}

		if (empty($dbList) && isset($lxDb['dbMap']['__default__'])) {
			$dbList = $lxDb['dbMap']['__default__'];
		}

		if (empty($dbList)) return $dbList;

		foreach ($dbList as $key => $value) {
			if (is_numeric($key)) {
				if (!array_key_exists($value, $dbList)) {
					throw new \Exception("Service '{$this->name}' require settings for DB '$value'", 400);
				}
				unset($dbList[$key]);
			} elseif (is_string($value)) {
				if (!isset($lxDb['dbList'][$value])) {
					throw new \Exception("There is no settings for DB '$value'", 400);
				}
				$dbList[$key] = $lxDb['dbList'][$value];
			}
		}

		return $dbList;
	}
}
