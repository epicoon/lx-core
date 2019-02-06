<?php

namespace lx;

class CliProcessor {
	private $commandsList = [];

	private $servicesList = null;
	private $service = null;
	private $module = null;

	private $args = [];

	private $consoleMap = [];
	private $needParam = false;
	private $params = [];
	private $invalidParams = [];
	private $keepProcess = false;

	public function __construct($commandsList) {
		$this->commandsList = $commandsList;
	}

	/**
	 *
	 * */
	public function handleCommand($commandType, $args, $service, $module) {
		$this->service = $service;
		$this->module = $module;
		$this->args = $args;

		$methodMap = [
			'help' => 'showHelp',
			'commands_list' => 'showCommands',
			'move' => 'move',
			'full_path' => 'fullPath',
			'reset_autoload_map' => 'resetAutoloadMap',

			'show_services' => 'showServices',
			'show_modules' => 'showModules',
			'show_models' => 'showModels',

			'migrate_check' => 'migrateCheck',
			'migrate_run' => 'migrateRun',

			'create_service' => 'createService',
			'create_module' => 'createModule',
		];

		$this->consoleMap = [];
		$this->needParam = false;
		$this->{$methodMap[$commandType]}();
		return $this->getResult();
	}

	/**
	 *
	 * */
	public function setParams($params) {
		$this->params = $params;
	}

	/**
	 *
	 * */
	public function getService() {
		return $this->service;
	}

	/**
	 *
	 * */
	public function getModule() {
		return $this->module;
	}


	/**************************************************************************************************************************
	 * Методы действий
	 *************************************************************************************************************************/

	/**
	 * //todo - когда будет что описывать в помощи, будет смысл ее делать
	 * */
	private function showHelp() {
		// if ($this->requireParam('opa')) {
		// 	$this->outln('Help is in developing... sorry. Use "\cl" or "commands-list" instead ;)');
		// 	$this->in('opa', 'Giv me opa: ');
		// 	return;
		// }
		// if ($this->requireParam('opa2')) {
		// 	$this->outln('MOOORE');
		// 	$this->in('opa2', 'Giv me opa2: ');
		// 	return;
		// }
		// $this->outln('!!! ' . $this->params['opa'] . ' ' . $this->params['opa2']);

		$this->outln('Help is in developing... sorry. Use "\cl" or "commands-list" instead ;)');
	}

	/**
	 * Исходя из self::COMMAND автоматически строит список существующих команд.
	 * Поэтому важно этому массиву давать вразумительные ключи
	 * */
	private function showCommands() {
		$arr = [];
		foreach ($this->commandsList as $key => $keywords) {
			$arr[] = [
				ucfirst( str_replace('_', ' ', $key) ),
				implode(', ', (array)$keywords)
			];
		}

		$arr = Console::normalizeTable($arr, '.');
		foreach ($arr as $row) {
			$this->out($row[0], ': ', ['decor' => 'b']);
			$this->outln($row[1]);
		}
	}

	/**
	 * Переместиться к сервису. Если нет аргумента - в корень приложения
	 * В качестве аргумента можно ввести имя сервиса, либо его индекс в листе сервисов, используя ключи -i или --index
	 * */
	private function move() {
		// Возвращение в приложение
		if (empty($this->args)) {
			$this->module = null;
			$this->service = null;
			return;
		}

		$name = null;

		// Проверка именованных параметров
		$index = $this->getArg(['i', 'index']);
		if ($index !== null) {
			$services = $this->getServicesList();
			if ($index > count($services)) {
				$this->outln('Maximum allowable index is ' . count($services));
				return;
			}

			// Удалось определить нужный сервис, модуль скидываем если был
			$temp = array_slice($services, $index - 1, 1);
			$service = end($temp);
			$name = $service['name'];
			$this->module = null;
		}

		// Если сервис не был найден в именованных параметрах - проверим первый параметр
		if ($name === null) {
			$name = $this->getArg(0);
		}

		// Если и такого параметра нет - введены ошибочные данные
		if ($name === null) {
			$msg = $this->service
				? 'Entered parameters are wrong. Enter service (or module) name or use keys -i or --index to point service'
				: 'Entered parameters are wrong. Enter service name or use keys -i or --index to point service';
			$this->outln($msg);
			return;
		}

		// Если находимся в сервисе - надо совершить проверки
		if ($this->service !== null) {
			// Если введенное имя - имя сервиса - скидываем модуль, если был
			if (array_key_exists($name, $this->getServicesList())) {
				$this->module = null;
			// Иначе попробуем войти в модуль
			} else {
				$list = (new ModuleBrowser($this->service))->getModulesList();
				if (array_search($name, $list['dynamic']) !== false) {
					$this->outln('Only static modules are available for edit from console');
					return;
				}
				if (!array_key_exists($name, $list['static'])) {
					$this->outln("Module '$name' is not exist");
					return;
				}

				$this->module = $this->service->getModule($name);
				return;
			}
		}

		try {
			$this->service = Service::create($name);
		} catch (\Exception $e) {
			$this->outln("Service '$name' not found");
			return;
		}
	}

	/**
	 * Выведет полный путь к корню приложения, сервиса или модуля - в зависимости от того, где находимся
	 * Или может принять в качестве аргумента имя сервися или модуля
	 * */
	private function fullPath() {
		if (empty($this->args)) {
			if ($this->module) {
				$this->outln('Path: ' . $this->module->getPath());
			} elseif ($this->service) {
				$this->outln('Path: ' . $this->service->dir->getPath());
			} else {
				$this->outln('Path: ' . \lx::sitePath());
			}
			return;
		}

		$name = $this->getArg(0);
		if ($name) {
			if (preg_match('/:/', $name)) {
				try {
					$path = \lx::getModulePath($name);
					if ($path === null) {
						throw new \Exception('', 400);
					}
					$this->outln("Module '$name' path: " . $path);
					return;
				} catch (\Exception $e) {
					$this->outln("Module '$name' not found");
					return;
				}
			} else {
				try {
					$service = Service::create($name);
					$this->outln("Service '$name' path: " . $service->dir->getPath());
					return;
				} catch (\Exception $e) {
					$this->outln("Service '$name' not found");
					return;
				}
			}
		}

		$this->outln('Wrong entered parameters');
	}

	/**
	 * Построение карты автозагрузки заново
	 * */
	private function resetAutoloadMap() {
		(new AutoloadMapBuilder())->createCommonAutoloadMap();
		$this->outln('Done');
	}

	/**
	 * Отобразить все сервисы приложения
	 * */
	private function showServices() {
		$data = $this->getServicesList();
		$data = Console::normalizeTable($data);

		$this->outln('Services list:', ['decor' => 'b']);
		$counter = 0;
		foreach ($data as $value) {
			$name = $value['name'];
			$path = $value['path'];
			$this->out((++$counter) . '. ', ['decor' => 'b']);
			$this->out('Name: ', ['decor' => 'b']);
			$this->out($name . '  ');
			$this->out('Path: ', ['decor' => 'b']);
			$this->outln($path);
		}
	}

	/**
	 * Отобразить модули сервиса, имя которого передано параметром, либо в котором находимся
	 * */
	private function showModules() {
		$service = null;
		$serviceName = $this->getArg(0);
		if ($serviceName) {
			try {
				$service = Service::create($serviceName);
			} catch (\Exception $e) {
				$this->outln("Service '$name' not found");
				return;
			}
		}
		if ($service === null) {
			$service = $this->service;
		}

		if ($service === null) {
			$this->outln("Modules belong to services. Input the service name or enter one of them");
			return;
		}

		$modules = (new ModuleBrowser($service))->getModulesList();
		/*[
			'dynamic' => [...names]
			'static' => [...{name=>pathInService}]
		]*/
		$dynamic = $modules['dynamic'];
		$static = $modules['static'];

		$this->outln('* * * Modules for service "'. $service->name .'" * * *', ['decor' => 'b']);

		$this->out('Dynamic modules:', ['decor' => 'b']);
		if (empty($dynamic)) {
			$this->outln(' NONE');
		} else {
			$this->outln();
			foreach ($dynamic as $name) {
				$this->out('* ', ['decor' => 'b']);
				$this->outln($name);
			}
		}

		$this->out('Static modules:', ['decor' => 'b']);
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

	/**
	 * Отображает базовую информацию по моделям
	 * Может принимать аргумент со значением:
	 * [-s | --service]
	 * [-m] - 'need-migrate' | '!need-migrate'
	 * */
	private function showModels() {
		$service = null;
		$serviceName = $this->getArg(0);
		if (!$serviceName) {
			$serviceName = $this->getArg('s');
		}
		if ($serviceName) {
			try {
				$service = Service::create($serviceName);
			} catch (\Exception $e) {
				$this->outln("Service '$name' not found");
				return;
			}
		}
		if ($service === null) {
			$service = $this->service;
		}

		if ($this->service === null) {
			$this->outln("Models belong to services. Input the service name or enter one of them");
			return;
		}

		if (!$this->checkArgs([
			'm' => ['need-migrate', '!need-migrate']
		])) {
			return;
		}

		$data = [[
			'name' => 'Name',
			'needMigrate' => 'Need migrate',
			'needTable' => 'Need table',
			'changed' => 'Changed'
		]];
		$info = (new ModelBrowser($this->service))->getModelsInfo(['needTable', 'hasChanges']);
		foreach ($info as $modelName => $modelInfo) {
			$needTable = $modelInfo['needTable'];
			$changed = $modelInfo['hasChanges'];
			if ($this->getArg('m') == 'need-migrate') {
				if (!$needTable && !$changed) continue;
			} elseif ($this->getArg('m') == '!need-migrate') {
				if ($needTable || $changed) continue;
			}
			$data[] = [
				'name' => $modelName,
				'needMigrate' => ($needTable || $changed) ? 'yes' : 'no',
				'needTable' => $needTable ? 'yes' : 'no',
				'changed' => $changed ? 'yes' : 'no',
			];
		}

		$data = Console::normalizeTable($data);
		foreach ($data as $i => $row) {
			if ($i) $this->outln($row['name'].' | '.$row['needMigrate'].' | '.$row['needTable'].' | '.$row['changed']);
			else $this->outln($row['name'].' | '.$row['needMigrate'].' | '.$row['needTable'].' | '.$row['changed'], ['decor' => 'b']);
		}
	}

	/**
	 * Посмотреть каким моделям нужны миграции
	 * Аргументы:
	 * [0] - имя модуля, необязательный
	 * [1] - имя модели, необязательный
	 * */
	private function migrateCheck() {
		$service = $this->getArg(0);
		$model = $this->getArg(1);
		if ($service) {
			try {
				$service = Service::create($service);
			} catch (\Exception $e) {
				$this->outln("Service '$service' not found");
				return;
			}
		}

		//todo - проверка валидности имени модуля

		$servicesMigrateInfo = $this->getServicesMigrateInfo($service, $model);

		if (empty($servicesMigrateInfo)) {
			$this->outln('No models need migrations', ['decor' => 'b']);
		} else {
			$this->outln('Following models need migrations:', ['decor' => 'b']);
			foreach ($servicesMigrateInfo as $info) {
				$service = $info['service'];
				$this->out('Service: ', ['decor' => 'b']);
				$this->outln($service->name);
				foreach ($info['models'] as $data) {
					$this->outln('-', $data['name']);
				}
			}
		}
	}

	/**
	 * Запуск миграций
	 * Аргументы:
	 * [0] - имя модуля, необязательный
	 * [1] - имя модели, необязательный
	 * */
	private function migrateRun() {
		$service = $this->getArg(0);
		if ($service) {
			try {
				$service = Service::create($service);
			} catch (\Exception $e) {
				$this->outln("Service '$service' not found");
				return;
			}
		}
		$model = $this->getArg(1);
		$servicesMigrateInfo = $this->getServicesMigrateInfo($service, $model);
		if (empty($servicesMigrateInfo)) {
			$this->outln('No models need migrations', ['decor' => 'b']);
			return;
		}
		
		$migrator = new MigrationManager();
		foreach ($servicesMigrateInfo as $info) {
			$this->out('Migration for service: ', ['decor' => 'b'] );
			$this->outln($info['service']->name, '...');

			foreach ($info['models'] as $data) {
				$this->outln('--- model:', $data['name']);
				$migrator->runModel($info['service'], $data['name'], $data['path']);
			}
		}

		$this->outln('Done', ['decor' => 'b']);
	}

	/**
	 * Создание нового сервиса
	 * //todo - флаг кастомного создания
	 * */
	private function createService() {
		$dirs = \lx::getConfig('packagesMap');
		if ($dirs) {
			$dirs = (array)$dirs;
		}

		if (!is_array($dirs) || empty($dirs)) {
			$this->outln("Application configuration 'packagesMap' not found");
			$this->done();
			return;
		}

		if ($this->requireParam('name')) {
			$name = $this->getArg(0);
			if (!$name) {
				$this->in('name', 'You need to enter new service name: ', ['decor' => 'b']);
				return;
			}
			$this->params['name'] = $name;
		}
		//todo проверить корректрость имени регуляркой
		$name = $this->params['name'];

		if (count($dirs) == 1) {
			$this->createServiceProcess($name, $dirs[0]);
			$this->done();
			return;
		}

		if ($this->requireParam('index')) {
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
		$i = $this->params['index'];

		if ($i == 'q') {
			$this->outln('Aborted');
			$this->done();
			return;
		}
		if (!is_numeric($i) || $i <= 0 || $i > count($dirs)) {
			$this->invalidParam('index');
			return;
		}

		$this->createServiceProcess($name, $dirs[$i - 1]);
		$this->done();
	}

	/**
	 * Создание нового модуля
	 * //todo - флаг кастомного создания
	 * */
	private function createModule() {
		// Для создания модуля нужно находиться в сервисе
		if ($this->service === null) {
			$this->outln("Modules belong to services. Enter the service");
			$this->done();
			return;
		}

		if ($this->requireParam('name')) {
			$name = $this->getArg(0);
			if (!$name) {
				$this->in('name', 'You need to enter new module name: ', ['decor' => 'b']);
				return;
			}
			$this->params['name'] = $name;
		}
		//todo проверить корректрость имени регуляркой
		$name = $this->params['name'];

		// Смотрим по конфигу - какие каталоги содержат модули
		$moduleDirs = $this->service->getConfig('service.modules');
		if ($moduleDirs) {
			$moduleDirs = (array)$moduleDirs;
		}
		if (!is_array($moduleDirs) || empty($moduleDirs)) {
			$this->outln("Service configuration 'modules' not found");
			$this->done();
			return;
		}

		// Если у сервиса только один каталог для модулей - сразу создаем там новый модуль
		if (count($moduleDirs) == 1) {
			$path = $moduleDirs[0];
			$this->createModuleProcess($this->service, $name, $path);
			$this->done();
			return;
		}

		if ($this->requireParam('index')) {
			$this->outln('Available directories for new module (relative to the service directory)::', ['decor' => 'b']);
			$counter = 0;
			foreach ($moduleDirs as $dirPath) {
				$this->out((++$counter) . '. ', ['decor' => 'b']);
				$this->outln($dirPath == '' ? '/' : $dirPath);
			}
			$this->out('q. ', ['decor' => 'b']);
			$this->outln('Quit');
			$this->in('index', 'Choose number of directory: ', ['decor' => 'b']);
			return;
		}
		$i = $this->params['index'];

		if ($i == 'q') {
			$this->outln('Aborted');
			$this->done();
			return;
		}
		if (!is_numeric($i) || $i <= 0 || $i > count($dirs)) {
			$this->invalidParam('index');
			return;
		}

		$this->createModuleProcess($this->service, $name, $moduleDirs[$i - 1]);
		$this->done();
	}


	/**************************************************************************************************************************
	 * Методы, обслуживающие базовую работу процессора
	 *************************************************************************************************************************/

	/**
	 *
	 * */
	private function getResult() {
		return [
			'output' => $this->consoleMap,
			'params' => $this->params,
			'invalidParams' => $this->invalidParams,
			'need' => $this->needParam,
			'keepProcess' => $this->keepProcess,
		];
	}

	/**
	 *
	 * */
	private function requireParam($name) {
		return !array_key_exists($name, $this->params);
	}

	/**
	 *
	 * */
	private function invalidParam($name) {
		unset($this->params[$name]);
		$this->invalidParams[] = $name;
		$this->keepProcess = true;
	}

	/**
	 *
	 * */
	private function done() {
		$this->params = [];
		$this->invalidParams = [];
		$this->keepProcess = false;
	}

	/**
	 *
	 * */
	private function out($text, $decor = []) {
		$this->consoleMap[] = ['out', $text, $decor];
	}

	/**
	 *
	 * */
	private function outln($text = '', $decor = []) {
		$this->consoleMap[] = ['outln', $text, $decor];	
	}

	/**
	 *
	 * */
	private function in($needParam, $text, $decor = []) {
		$this->needParam = $needParam;
		$this->consoleMap[] = ['in', $text, $decor];		
	}

	/**
	 * Получить агрумент по ключу (или индексу, если массив аргументов перечислимый)
	 * */
	private function getArg($key) {
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
	 * @param array $arr - массив вариантов значений. Ключи массива - ключи введенных аргументов, значения массива - допустимые значения для аргументов
	 * */
	private function checkArgs($arr) {
		foreach ($arr as $i => $variants) {
			$arg = $this->getArg($i);
			if ($arg === null) continue;
			if (array_search($arg, $variants) === false) {
				$this->outln("Argument [$i] = '$arg' is not valid. Available are: " . implode(', ', $variants));
				return false;
			}
		}
		return true;
	}


	/**************************************************************************************************************************
	 * Методы, обслуживающие действия
	 *************************************************************************************************************************/

	/**
	 *
	 * */
	private function getServicesList() {
		if ($this->servicesList === null) {
			$this->resetServicesList();
		}
		return $this->servicesList;
	}

	/**
	 *
	 * */
	private function resetServicesList() {
		$services = PackageBrowser::getServicesList();
		$data = [];
		foreach ($services as $name => $path) {
			$data[$name] = [
				'name' => $name,
				'path' => $path,
				'object' => Service::create($name),
			];
		}
		uksort($data, 'strcasecmp');
		$this->servicesList = $data;
	}

	/**
	 * Собирает информацию - для каких сервисов для каких моделей нужно делать миграции
	 * */
	private function getServicesMigrateInfo($service = null, $modelName = null) {
		// Определяем участвующие сервисы
		$services = $service === null
			? []
			: $service;
		if (!is_array($service)) {
			$service = [$service];
		}
		if (empty($services)) {
			$servicesList = $this->getServicesList();
			foreach ($servicesList as $data) {
				$services[] = $data['object'];
			}
		}

		// Определяем участвующие модели
		$modelNames = $modelName === null
			? []
			: (array)$modelName;

		$map = [];
		foreach ($services as $service) {
			$modelsData = $service->conductor->getModelsPath();
			$serviceModels = [];
			if (empty($modelNames)) {
				$serviceModels = $modelsData;
			} else {
				foreach ($modelNames as $modelName) {
					if (array_key_exists($modelName, $modelsData)) {
						$serviceModels[$modelName] = $modelsData[$modelName];
					}
				}
			}

			$analizer = new ModelBrowser($service);
			foreach ($serviceModels as $modelName => $path) {
				$needTable = $analizer->checkModelNeedTable($modelName);
				$changed = $analizer->checkModelNeedChange(['path' => $path]);
				if (!$needTable && !$changed) {
					continue;
				}
				if (!array_key_exists($service->name, $map)) {
					$map[$service->name] = [
						'service' => $service,
						'models' => [],
					];
				}
				$map[$service->name]['models'][] = [
					'name' => $modelName,
					'path' => $path,
					'needTable' => $needTable,
					'changed' => $changed,
				];
			}
		}
		return $map;
	}

	/**
	 *
	 * */
	private function createServiceProcess($name, $path) {
		$editor = new ServiceEditor();
		try {
			$dir = $editor->createService($name, $path);

			(new AutoloadMapBuilder())->createCommonAutoloadMap();
			Autoloader::getInstance()->map->reset();
			$this->resetServicesList();

			Console::out('New service created in: ');
			Console::outln($dir->getPath(), ['decor' => 'u']);
		} catch (\Exception $e) {
			Console::outln('Module was not created. ' . $e->getMessage());
		}
	}

	/**
	 *
	 * */
	private function createModuleProcess($service, $name, $path) {
		$editor = new ModuleEditor($service);
		try {
			$dir = $editor->createModule($name, $path);
			Console::out('New module created in: ');
			Console::outln($dir->getPath(), ['decor' => 'u']);
		} catch (\Exception $e) {
			Console::outln('Module was not created. ' . $e->getMessage());
		}
	}
}
