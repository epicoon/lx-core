<?php

namespace lx;

/**
 * Class CliProcessor
 * @package lx
 */
class CliProcessor extends BaseObject
{
	use ApplicationToolTrait;

	const COMMAND_TYPE_COMMON = 5;
	const COMMAND_TYPE_CONSOLE_ONLY = 10;
	const COMMAND_TYPE_WEB_ONLY = 15;

	const SELF_COMMANDS = [
		'exit' => '\q',
		'help' => ['\h', 'help'],
		'move' => ['\g', 'goto'],
		'full_path' => ['\p', 'fullpath'],
		'reset_autoload_map' => ['\amr', 'autoload-map-reset'],
		'reset_js_autoload_map' => ['\amrjs', 'autoload-map-reset-js'],

		'show_services' => ['\sl', 'services-list'],
		'show_plugins' => ['\pl', 'plugins-list'],

		'create_service' => ['\cs', 'create-service'],
		'create_plugin' => ['\cp', 'create-plugin'],
	];

	const METHOD_MAP = [
		'help' => 'showHelp',
		'move' => 'move',
		'full_path' => 'fullPath',
		'reset_autoload_map' => 'resetAutoloadMap',
		'reset_js_autoload_map' => 'resetJsAutoloadMap',

		'show_services' => 'showServices',
		'show_plugins' => 'showPlugins',

		'create_service' => 'createService',
		'create_plugin' => 'createPlugin',
	];

	/** @var array */
	private $_extensions;

	/** @var array */
	private $_methodMap;

	/** @var array */
	private $servicesList;

	/** @var Service|null */
	private $service;

	/** @var Plugin|null */
	private $plugin;

	/** @var array */
	private $args = [];

	/** @var array */
	private $consoleMap = [];

	/** @var string|false */
	private $needParam = false;

	/** @var array */
	private $params = [];

	/** @var array */
	private $invalidParams = [];

	/** @var bool */
	private $keepProcess = false;

	/** @var array */
	private $data = [];

	/**
	 * @param array $types
	 * @param array $excludedTypes
	 * @return array
	 */
	public function getCommandsList($types = null, $excludedTypes = null)
	{
		return array_merge(
			self::SELF_COMMANDS,
			$this->getCommandsExtensionList($types, $excludedTypes)
		);
	}

	/**
	 * @param string $commandType
	 * @param array $args
	 * @param Service|null $service
	 * @param Plugin|null $plugin
	 * @return array
	 */
	public function handleCommand($commandType, $args, $service, $plugin)
	{
		$this->service = $service;
		$this->plugin = $plugin;
		$this->args = $args;
		$this->consoleMap = [];
		$this->needParam = false;

		$this->invokeMethod($commandType);
		return $this->getResult();
	}

	/**
	 * @return array
	 */
	public function getData()
	{
		return $this->data;
	}

	/**
	 * @param array $data
	 */
	public function setData($data)
	{
		$this->data = $data;
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 */
	public function addData($name, $value)
	{
		$this->data[$name] = $value;
	}

	/**
	 * @return array
	 */
	public function getServicesList()
	{
		if ($this->servicesList === null) {
			$this->resetServicesList();
		}
		return $this->servicesList;
	}

	/**
	 * @return Service|null
	 */
	public function getService()
	{
		return $this->service;
	}

	/**
	 * @return Plugin|null
	 */
	public function getPlugin()
	{
		return $this->plugin;
	}

	/**
	 * @param array $params
	 */
	public function setParams($params)
	{
		$this->params = $params;
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 */
	public function setParam($name, $value)
	{
		$this->params[$name] = $value;
	}

	/**
	 * @param string $name
	 * @return mixed|null
	 */
	public function getParam($name)
	{
		if (!array_key_exists($name, $this->params)) {
			return null;
		}

		return $this->params[$name];
	}

	/**
	 * @return array
	 */
	public function getResult()
	{
		$result = [
			'output' => $this->consoleMap,
			'params' => $this->params,
			'invalidParams' => $this->invalidParams,
			'need' => $this->needParam,
			'keepProcess' => $this->keepProcess,
		];

		if (!empty($this->getData())) {
			$result['data'] = $this->getData();
		}

		return $result;
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function hasParam($name)
	{
		return array_key_exists($name, $this->params);
	}

	/**
	 * @param string $name
	 */
	public function invalidateParam($name)
	{
		unset($this->params[$name]);
		$this->invalidParams[] = $name;
		$this->keepProcess = true;
	}

	/**
	 * Method to be used by CLI executors to stop process
	 */
	public function done()
	{
		$this->params = [];
		$this->invalidParams = [];
		$this->keepProcess = false;
	}

	/**
	 * @param string $text
	 * @param array $decor
	 */
	public function out($text, $decor = [])
	{
		$this->consoleMap[] = ['out', $text, $decor];
	}

	/**
	 * @param string $text
	 * @param array $decor
	 */
	public function outln($text = '', $decor = [])
	{
		$this->consoleMap[] = ['outln', $text, $decor];
	}

	/**
	 * @param string $needParam
	 * @param string $text
	 * @param array $decor
	 */
	public function in($needParam, $text, $decor = [])
	{
		$this->needParam = $needParam;
		$this->consoleMap[] = ['in', $text, $decor];
	}

	/**
	 * @param string|int|array $key
	 * @return mixed|null
	 */
	public function getArg($key)
	{
		if (is_array($key)) {
			foreach ($key as $item) {
				if (array_key_exists($item, $this->args)) {
					return $this->args[$item];
				}
			}
			return null;
		}

		if (array_key_exists($key, $this->args)) {
			return $this->args[$key];
		}
		return null;
	}

	/**
	 * @param array $arr - массив вариантов значений
	 * Ключи массива - ключи введенных аргументов, значения массива - допустимые значения для аргументов
	 * */

	/**
	 * Method recieves validation map where keys are console argument keys
	 * and values are available values for that arguments
	 *
	 * @param array $rulesMap
	 * @return bool
	 */
	public function validateArgs($rulesMap)
	{
		foreach ($rulesMap as $i => $variants) {
			$arg = $this->getArg($i);
			if ($arg === null) {
				continue;
			}

			if (array_search($arg, $variants) === false) {
				$this->outln("Argument [$i] = '$arg' is not valid. Available are: " . implode(', ', $variants));
				return false;
			}
		}
		return true;
	}


	/*******************************************************************************************************************
	 * PRIVATE FOR CONSOLE COMMANDS
	 ******************************************************************************************************************/

	/**
	 * Method makes description for commands automatically based on command keys
	 */
	private function showHelp()
	{
		$arr = [];
		$list = $this->getCommandsList([
			self::COMMAND_TYPE_COMMON,
			self::COMMAND_TYPE_CONSOLE_ONLY,
		]);
		foreach ($list as $key => $keywords) {
			$key = StringHelper::camelToSnake($key);
			$arr[] = [
				ucfirst(str_replace('_', ' ', $key)),
				implode(', ', (array)$keywords)
			];
		}

		$arr = Console::normalizeTable($arr, '.');
		foreach ($arr as $row) {
			$this->out($row[0] . ': ', ['decor' => 'b']);
			$this->outln($row[1]);
		}
	}

	/**
	 * Move to a service
	 */
	private function move()
	{
		// Return to application
		if (empty($this->args)) {
			$this->plugin = null;
			$this->service = null;
			return;
		}

		$name = null;

		// Check named arguments
		$index = $this->getArg(['i', 'index']);
		if ($index !== null) {
			$services = $this->getServicesList();
			if ($index > count($services)) {
				$this->outln('Maximum allowable index is ' . count($services));
				return;
			}

			$temp = array_slice($services, $index - 1, 1);
			$service = end($temp);
			$name = $service['name'];
			$this->plugin = null;
		}

		// Check the first unnamed parameter
		if ($name === null) {
			$name = $this->getArg(0);
		}

		if ($name === null) {
			$msg = $this->service
				? 'Entered parameters are wrong. Enter service (or plugin) name or use keys -i or --index to point service'
				: 'Entered parameters are wrong. Enter service name or use keys -i or --index to point service';
			$this->outln($msg);
			return;
		}

		if ($this->service !== null) {
			if (array_key_exists($name, $this->getServicesList())) {
				$this->plugin = null;
			} else {
				$list = PluginBrowser::getPluginsDataMap($this->service);
				if (array_key_exists($name, $list['dynamic'])) {
					$this->outln('Only static plugins are available for edit from console');
					return;
				}
				if (!array_key_exists($name, $list['static'])) {
					$this->outln("Plugin '$name' is not exist");
					return;
				}

				$this->plugin = $this->service->getPlugin($name);
				return;
			}
		}

		try {
			$this->service = $this->app->getService($name);
		} catch (\Exception $e) {
			$this->outln("Service '$name' not found");
			return;
		}
	}

	/**
	 * Prints full path to application or service or plugin due to current location or entered argument
	 */
	private function fullPath()
	{
		if (empty($this->args)) {
			if ($this->plugin) {
				$this->outln('Path: ' . $this->plugin->getPath());
			} elseif ($this->service) {
				$this->outln('Path: ' . $this->service->getPath());
			} else {
				$this->outln('Path: ' . \lx::sitePath());
			}
			return;
		}

		$name = $this->getArg(0);
		if ($name) {
			if (preg_match('/:/', $name)) {
				try {
					$path = $this->app->getPluginPath($name);
					if ($path === null) {
						throw new \Exception('', 400);
					}
					$this->outln("Plugin '$name' path: " . $path);
					return;
				} catch (\Exception $e) {
					$this->outln("Plugin '$name' not found");
					return;
				}
			} else {
				try {
					$service = $this->app->getService($name);
					$this->outln("Service '$name' path: " . $service->getPath());
					return;
				} catch (\Exception $e) {
					$this->outln("Service '$name' not found");
					return;
				}
			}
		}

		$this->outln('Wrong entered parameters');
	}

	private function resetAutoloadMap()
	{
		(new AutoloadMapBuilder())->createCommonAutoloadMap();
		$this->outln('Done');
	}

	private function resetJsAutoloadMap()
	{
		if ($this->getArg(0) == 'core') {
			$this->outln('Creating core map...');
			(new JsModuleMapBuilder())->renewCore();
			$this->outln('Done');
			return;
		}

		$service = null;
		$serviceName = $this->getArg(0);
		if (!$serviceName) {
			$serviceName = $this->getArg('s');
		}
		if ($serviceName) {
			try {
				$service = $this->app->getService($serviceName);
			} catch (\Exception $e) {
				$this->outln("Service '$name' not found");
				return;
			}
		}
		if ($service === null) {
			$service = $this->service;
		}

		if ($service === null) {
			$this->outln('Creating full map...');
			(new JsModuleMapBuilder())->renewAllServices();
			$this->outln('Done');
			return;
		}

		$this->outln('Creating map for service "' . $service->name . '"...');
		(new JsModuleMapBuilder())->renewService($service);
		$this->outln('Done');
	}

	private function showServices()
	{
		$temp = $this->getServicesList();
		$data = [];
		$counter = 0;
		foreach ($temp as $value) {
			$data[] = [
				'num' => '' . (++$counter) . '.',
				'name' => $value['name'],
				'path' => $value['path'],
			];
		}
		$data = Console::normalizeTable($data);

		$this->outln('Services list:', ['decor' => 'b']);
		$counter = 0;
		foreach ($data as $value) {
			$num = $value['num'];
			$name = $value['name'];
			$path = $value['path'];
			$this->out($num . ' ', ['decor' => 'b']);
			$this->out('Name: ', ['decor' => 'b']);
			$this->out($name . '  ');
			$this->out('Path: ', ['decor' => 'b']);
			$this->outln($path);
		}
	}

	private function showPlugins()
	{
		$service = null;
		$serviceName = $this->getArg(0);
		if ($serviceName) {
			try {
				$service = $this->app->getService($serviceName);
			} catch (\Exception $e) {
				$this->outln("Service '$name' not found");
				return;
			}
		}
		if ($service === null) {
			$service = $this->service;
		}

		if ($service === null) {
			$this->outln("Plugins belong to services. Input the service name or enter one of them");
			return;
		}

		$plugins = PluginBrowser::getPluginsDataMap($service);
		$dynamic = $plugins['dynamic'];
		$static = $plugins['static'];

		$this->outln('* * * Plugins for service "' . $service->name . '" * * *', ['decor' => 'b']);

		$this->out('Dynamic plugins:', ['decor' => 'b']);
		if (empty($dynamic)) {
			$this->outln(' NONE');
		} else {
			$this->outln();
			foreach ($dynamic as $name => $data) {
				$this->out('* ', ['decor' => 'b']);
				$this->outln($name);
			}
		}

		$this->out('Static plugins:', ['decor' => 'b']);
		if (empty($static)) {
			$this->outln(' NONE');
		} else {
			$this->outln();
			$data = [];
			foreach ($static as $name => $path) {
				$data[] = [
					'name' => $name,
					'path' => $path
				];
			}
			$data = Console::normalizeTable($data);
			foreach ($data as $value) {
				$name = $value['name'];
				$path = $value['path'];
				$this->out('* Name: ', ['decor' => 'b']);
				$this->out($name . '  ');
				$this->out('Path: ', ['decor' => 'b']);
				$this->outln($path);
			}
		}
	}

	private function createService()
	{
		$dirs = $this->app->getConfig('packagesMap');
		if ($dirs) {
			$dirs = (array)$dirs;
		}

		if (!is_array($dirs) || empty($dirs)) {
			$this->outln("Application configuration 'packagesMap' not found");
			$this->done();
			return;
		}

		if (!$this->hasParam('name')) {
			$name = $this->getArg(0);
			if (!$name) {
				$this->in('name', 'You need to enter new service name: ', ['decor' => 'b']);
				return;
			}
			$this->setParam('name', $name);
		}
		$name = $this->getParam('name');
		if (!preg_match('/^[a-zA-Z_][\w-]*?(?:\/[a-zA-Z_])?[\w-]*$/', $name)) {
			$this->in(
				'name',
				'Entered service name is incorrect, enter correct name: ',
				['decor' => 'b']
			);
			return;
		}

		if (count($dirs) == 1) {
			$this->createServiceProcess($name, $dirs[0]);
			$this->done();
			return;
		}

		if (!$this->hasParam('index')) {
			$this->outln('Available directories for new service:', ['decor' => 'b']);
			$counter = 0;
			foreach ($dirs as $dirPath) {
				$this->out((++$counter) . '. ', ['decor' => 'b']);
				$this->outln($dirPath == '' ? '/' : $dirPath);
			}
			$this->out('q. ', ['decor' => 'b']);
			$this->outln('Quit');
			$this->in('index', 'Choose number of directory: ', ['decor' => 'b']);
			return;
		}
		$i = $this->getParam('index');

		if ($i == 'q') {
			$this->outln('Aborted');
			$this->done();
			return;
		}
		if (!is_numeric($i) || $i <= 0 || $i > count($dirs)) {
			$this->invalidateParam('index');
			return;
		}

		$this->createServiceProcess($name, $dirs[$i - 1]);
		$this->done();
	}

	private function createPlugin()
	{
		if ($this->service === null) {
			$this->outln("Plugins belong to services. Enter the service");
			$this->done();
			return;
		}

		if (!$this->hasParam('name')) {
			$name = $this->getArg(0);
			if (!$name) {
				$this->in('name', 'You need to enter new plugin name: ', ['decor' => 'b']);
				return;
			}
			$this->setParam('name', $name);
		}
		$name = $this->getParam('name');
		if (!preg_match('/^[a-zA-Z_][\w-]*$/', $name)) {
			$this->in(
				'name',
				'Entered plugin name is incorrect, enter correct name: ',
				['decor' => 'b']
			);
			return;
		}

		$pluginDirs = $this->service->getConfig('service.plugins');
		if ($pluginDirs) {
			$pluginDirs = (array)$pluginDirs;
		}
		if (!is_array($pluginDirs) || empty($pluginDirs)) {
			$this->outln("Service configuration 'plugins' not found");
			$this->done();
			return;
		}

		if (count($pluginDirs) == 1) {
			$path = $pluginDirs[0];
			$this->createPluginProcess($this->service, $name, $path);
			$this->done();
			return;
		}

		if (!$this->hasParam('index')) {
			$this->outln('Available directories for new plugin (relative to the service directory)::', ['decor' => 'b']);
			$counter = 0;
			foreach ($pluginDirs as $dirPath) {
				$this->out((++$counter) . '. ', ['decor' => 'b']);
				$this->outln($dirPath == '' ? '/' : $dirPath);
			}
			$this->out('q. ', ['decor' => 'b']);
			$this->outln('Quit');
			$this->in('index', 'Choose number of directory: ', ['decor' => 'b']);
			return;
		}
		$i = $this->getParam('index');

		if ($i == 'q') {
			$this->outln('Aborted');
			$this->done();
			return;
		}
		if (!is_numeric($i) || $i <= 0 || $i > count($dirs)) {
			$this->invalidateParam('index');
			return;
		}

		$this->createPluginProcess($this->service, $name, $pluginDirs[$i - 1]);
		$this->done();
	}


	/*******************************************************************************************************************
	 * PRIVATE FOR PROCESSOR INNER WORK
	 ******************************************************************************************************************/

	/**
	 * Actualization of services information
	 */
	private function resetServicesList()
	{
		$services = PackageBrowser::getServicePathesList();
		$data = [];
		foreach ($services as $name => $path) {
			$data[$name] = [
				'name' => $name,
				'path' => $path,
				'object' => $this->app->getService($name),
			];
		}
		uksort($data, 'strcasecmp');
		$this->servicesList = $data;
	}

	/**
	 * @param string $name
	 * @param string $path
	 */
	private function createServiceProcess($name, $path)
	{
		$editor = new ServiceEditor();
		$service = $editor->createService($name, $path);
		if ($service) {
			$this->resetServicesList();
			$this->out('New service created in: ');
			$this->outln($service->getPath(), ['decor' => 'u']);
		} else {
			$this->outln('Service was not created');
		}
	}

	/**
	 * @param Service $service
	 * @param string $name
	 * @param string $path
	 */
	private function createPluginProcess($service, $name, $path)
	{
		$editor = new PluginEditor($service);

		$plugin = $editor->createPlugin($name, $path);
		if ($plugin) {
			$dir = $plugin->directory;
			$this->out('New plugin created in: ');
			$this->outln($dir->getPath(), ['decor' => 'u']);
		} else {
			$this->outln('Plugin was not created');
		}
	}

	/**
	 * @param string $commandType
	 */
	private function invokeMethod($commandType)
	{
		$map = $this->getMethodMap();
		$command = $map[$commandType];
		if (is_string($command)) {
			if (method_exists($this, $command)) {
				$this->{$command}();
			} elseif (ClassHelper::implements($command, ServiceCliExecutorInterface::class)) {
				/** @var ServiceCliExecutorInterface $object */
				$object = new $command();
				$object->setProcessor($this);
				$object->run();
			}
		} elseif (
			is_array($command)
			&& ClassHelper::implements($command[0] ?? '', ServiceCliExecutorInterface::class)
		) {
			/** @var ServiceCliExecutorInterface $object */
			$object = new $command[0]();
			$method = $command[1] ?? '';
			if (method_exists($object, $method)) {
				$object->setProcessor($this);
				$object->{$command[1]}();
			}
		}
	}

	/**
	 * @param array $types
	 * @param array $excludedTypes
	 * @return array
	 */
	private function getCommandsExtensionList($types, $excludedTypes)
	{
		if ($this->_extensions === null) {
			$this->loadServiceExtensions();
		}

		return $this->extractCommandsExtensionList($types, $excludedTypes);
	}

	/**
	 * @param array $types
	 * @param array $excludedTypes
	 * @return array
	 */
	private function extractCommandsExtensionList($types, $excludedTypes)
	{
		$result = [];
		foreach ($this->_extensions as $serviceData) {
			foreach ($serviceData as $commandData) {
				if (!$this->validateCommandType($commandData, $types, $excludedTypes)) {
					continue;
				}

				$key = $this->getExtensionKey($commandData);
				$result[$key] = $commandData['command'];
			}
		}

		return $result;
	}

	/**
	 * @return array
	 */
	private function getMethodMap()
	{
		if ($this->_methodMap === null) {
			$this->_methodMap = array_merge(
				self::METHOD_MAP,
				$this->getMethodMapExtension()
			);
		}

		return $this->_methodMap;
	}

	/**
	 * @return array
	 */
	private function getMethodMapExtension()
	{
		if ($this->_extensions === null) {
			$this->loadServiceExtensions();
		}

		return $this->extractMethodMapExtension();
	}

	/**
	 * @return array
	 */
	private function extractMethodMapExtension()
	{
		$result = [];
		foreach ($this->_extensions as $serviceData) {
			foreach ($serviceData as $commandData) {
				$key = $this->getExtensionKey($commandData);
				$result[$key] = $commandData['handler'];
			}
		}

		return $result;
	}

	/**
	 * @param array $extension
	 * @return string
	 */
	private function getExtensionKey($extension)
	{
		return StringHelper::snakeToCamel(trim(((array)$extension['command'])[0], '\\'), ['_', '-']);
	}

	/**
	 * @param array $commandData
	 * @param array $types
	 * @param array $excludedTypes
	 * @return bool
	 */
	private function validateCommandType($commandData, $types, $excludedTypes)
	{
		$type = $commandData['type'] ?? self::COMMAND_TYPE_COMMON;
		$types = $types ?? [
				self::COMMAND_TYPE_COMMON,
				self::COMMAND_TYPE_CONSOLE_ONLY,
				self::COMMAND_TYPE_WEB_ONLY,
			];
		$excludedTypes = $excludedTypes ?? [];

		if (array_search($type, $excludedTypes) !== false) {
			return false;
		}

		if (array_search($type, $types) === false) {
			return false;
		}

		return true;
	}

	private function loadServiceExtensions()
	{
		if ($this->_extensions === null) {
			$this->_extensions = [];
		}

		$servicesList = $this->getServicesList();
		foreach ($servicesList as $serviceName => $serviceData) {
			$service = $serviceData['object'];
			$serviceCli = $service->cli;
			if (!$serviceCli || !$serviceCli instanceof ServiceCliInterface) {
				continue;
			}

			$this->_extensions[$serviceName] = $serviceCli->getExtensionData();
		}
	}
}
