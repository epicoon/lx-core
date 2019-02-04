<?php

namespace lx;

class Cli {
	const COMMANDS = [
		'exit' => '\q',
		'help' => ['\h', 'help'],
		'commands_list' => ['\cl', 'commands-list'],
		'full_path' => ['\fp', 'fullpath'],
		'move' => ['\g', 'goto'],
		'reset_autoload_map' => ['\ram', 'reset-autoload-map'],

		'show_services' => ['\sl', 'services-list'],
		'show_modules' => ['\ml', 'modules-list'],
		'show_models' => 'models-list',

		'migrate_check' => 'migrate-check',
		'migrate_run' => 'migrate-run',

		'create_service' => ['\cs', 'create-service'],
		'create_module' => ['\cm', 'create-module'],
	];
	/*
	//todo
	выбор создаваемых компонентов для нового модуля - какие каталоги, надо ли файл пееропределяющий сам модуль...
	удаление модуля

	запрос на какую-нибудь модель

	??? надо ли с блоками отсюда работать
		создание вью-блоков
		просмотр дерева имеющихся блоков
	*/

	private $servicesList = null;
	private $service = null;
	private $module = null;

	private $args = [];
	private $commandsHistory = [];
	private $commandsHistoryIndex = 0;


	/**
	 * Крутит консольный ввод пока не будет осуществлен выход из cli
	 * */
	public function run() {
		$command = null;

		while (!$this->checkCommand($command, 'exit')) {
			$text = 'lx-cli<';
			if ($this->module !== null) {
				$text .= 'module:' . $this->module->name . '>: ';
			} elseif ($this->service !== null) {
				$text .= 'service:' . $this->service->name . '>: ';
			} else {
				$text .= 'app>: ';
			}
			$input = Console::in(
				$text,
				['color' => 'yellow', 'decor' => 'b'],
				['color' => 'yellow'],
				[
					'up' => function() {
						if ($this->commandsHistoryIndex == 0) return;
						$this->commandsHistoryIndex--;
						Console::replaceInput($this->commandsHistory[$this->commandsHistoryIndex]);
					},
					'down' => function() {
						if ($this->commandsHistoryIndex == count($this->commandsHistory)) {
							return;
						}
						$this->commandsHistoryIndex++;
						if ($this->commandsHistoryIndex == count($this->commandsHistory)) {
							Console::replaceInput('');
							return;
						}
						Console::replaceInput($this->commandsHistory[$this->commandsHistoryIndex]);
					},
					'intercept' => [
						// TAB
						9 => function() use($text) {
							$currentInput = Console::getCurrentInput();
							$command = $this->tryFinishCommand($currentInput);
							if ($command) {
								if ($command['common'] == $currentInput) {
									Console::outln();
									Console::outln(implode('  ', $command['matches']));
									Console::out($text, ['color' => 'yellow', 'decor' => 'b']);
									Console::out($currentInput, ['color' => 'yellow']);
								} else {
									Console::replaceInput($command['common']);
								}
							}
						}
					]
				]
			);

			if ($input == '') continue;

			$this->commandsHistory[] = $input;
			$this->commandsHistoryIndex = count($this->commandsHistory);
			list ($command, $args) = $this->parseInput($input);
			$this->args = $args;

			switch (true) {
				case $this->checkCommand($command, 'exit'              ): break;
				case $this->checkCommand($command, 'help'              ): $this->showHelp(); break;
				case $this->checkCommand($command, 'commands_list'     ): $this->showCommands(); break;
				case $this->checkCommand($command, 'move'              ): $this->move(); break;
				case $this->checkCommand($command, 'full_path'         ): $this->fullPath(); break;
				case $this->checkCommand($command, 'reset_autoload_map'): $this->resetAutoloadMap(); break;

				case $this->checkCommand($command, 'show_services'): $this->showServices(); break;
				case $this->checkCommand($command, 'show_modules' ): $this->showModules(); break;
				case $this->checkCommand($command, 'show_models'  ): $this->showModels(); break;

				case $this->checkCommand($command, 'migrate_check'): $this->migrateCheck(); break;
				case $this->checkCommand($command, 'migrate_run'  ): $this->migrateRun(); break;

				//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
				case $this->checkCommand($command, 'create_service'): $this->createService(); break;
				case $this->checkCommand($command, 'create_module' ): $this->createModule(); break;


				default:
					Console::outln("Unknown command '$command'. Enter 'help' to see commands list");
					break;
			}
		}
	}


	/**************************************************************************************************************************
	 * PRIVATE
	 *************************************************************************************************************************/

	/**************************************************************************************************************************
	 * Методы действий
	 *************************************************************************************************************************/

	/**
	 * //todo - когда будет что описывать в помощи, будет смысл ее делать
	 * */
	private function showHelp() {
		Console::outln('Help is in developing... sorry. Use "\cl" or "commands-list" ;)');
	}

	/**
	 * Исходя из self::COMMAND автоматически строит список существующих команд.
	 * Поэтому важно этому массиву давать вразумительные ключи
	 * */
	private function showCommands() {
		$arr = [];
		foreach (self::COMMANDS as $key => $keywords) {
			$arr[] = [
				ucfirst( str_replace('_', ' ', $key) ),
				implode(', ', (array)$keywords)
			];
		}

		$arr = Console::normalizeTable($arr, '.');
		foreach ($arr as $row) {
			Console::out($row[0], ': ', ['decor' => 'b']);
			Console::outln($row[1]);
		}
	}

	/**
	 * Выведет полный путь к корню приложения, сервиса или модуля - в зависимости от того, где находимся
	 * Или может принять в качестве аргумента имя сервися или модуля
	 * */
	private function fullPath() {
		if (empty($this->args)) {
			if ($this->module) {
				Console::outln('Path: ' . $this->module->getPath());
			} elseif ($this->service) {
				Console::outln('Path: ' . $this->service->dir->getPath());
			} else {
				Console::outln('Path: ' . \lx::sitePath());
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
					Console::outln("Module '$name' path: " . $path);
					return;
				} catch (\Exception $e) {
					Console::outln("Module '$name' not found");
					return;
				}
			} else {
				try {
					$service = Service::create($name);
					Console::outln("Service '$name' path: " . $service->dir->getPath());
					return;
				} catch (\Exception $e) {
					Console::outln("Service '$name' not found");
					return;
				}
			}
		}

		Console::outln('Wrong entered parameters');
	}

	/**
	 * Построение карты автозагрузки заново
	 * */
	private function resetAutoloadMap() {
		(new AutoloadMapBuilder())->createCommonAutoloadMap();
		Console::outln('Done');
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
				Console::outln('Maximum allowable index is ' . count($services));
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
			Console::outln($msg);
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
					Console::outln('Only static modules are available for edit from console');
					return;
				}
				if (!array_key_exists($name, $list['static'])) {
					Console::outln("Module '$name' is not exist");
					return;
				}

				$this->module = $this->service->getModule($name);
				return;
			}
		}

		try {
			$this->service = Service::create($name);
		} catch (\Exception $e) {
			Console::outln("Service '$name' not found");
			return;
		}
	}

	/**
	 * Отобразить все сервисы приложения
	 * */
	private function showServices() {
		$data = $this->getServicesList();
		$data = Console::normalizeTable($data);

		Console::outln('Services list:', ['decor' => 'b']);
		$counter = 0;
		foreach ($data as $value) {
			$name = $value['name'];
			$path = $value['path'];
			Console::out((++$counter) . '. ', ['decor' => 'b']);
			Console::out('Name: ', ['decor' => 'b']);
			Console::out($name . '  ');
			Console::out('Path: ', ['decor' => 'b']);
			Console::outln($path);
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
				Console::outln("Service '$name' not found");
				return;
			}
		}
		if ($service === null) {
			$service = $this->service;
		}

		if ($service === null) {
			Console::outln("Modules belong to services. Input the service name or enter one of them");
			return;
		}

		$modules = (new ModuleBrowser($service))->getModulesList();
		/*[
			'dynamic' => [...names]
			'static' => [...{name=>pathInService}]
		]*/
		$dynamic = $modules['dynamic'];
		$static = $modules['static'];

		Console::outln('* * * Modules for service "'. $service->name .'" * * *', ['decor' => 'b']);

		Console::out('Dynamic modules:', ['decor' => 'b']);
		if (empty($dynamic)) {
			Console::outln(' NONE');
		} else {
			Console::outln();
			foreach ($dynamic as $name) {
				Console::out('* ', ['decor' => 'b']);
				Console::outln($name);
			}
		}

		Console::out('Static modules:', ['decor' => 'b']);
		if (empty($static)) {
			Console::outln(' NONE');
		} else {
			Console::outln();
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
				Console::out('* Name: ', ['decor' => 'b']);
				Console::out($name . '  ');
				Console::out('Path: ', ['decor' => 'b']);
				Console::outln($path);
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
		if ($serviceName) {
			try {
				$service = Service::create($serviceName);
			} catch (\Exception $e) {
				Console::outln("Service '$name' not found");
				return;
			}
		}
		if ($service === null) {
			$service = $this->service;
		}

		if ($this->service === null) {
			Console::outln("Models belong to services. Input the service name or enter one of them");
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
			if ($this->getArg(0) == 'need-migrate') {
				if (!$needTable && !$changed) continue;
			} elseif ($this->getArg(0) == '!need-migrate') {
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
			if ($i) Console::outln($row['name'], '|', $row['needMigrate'], '|', $row['needTable'], '|', $row['changed']);
			else Console::outln($row['name'], '|', $row['needMigrate'], '|', $row['needTable'], '|', $row['changed'], ['decor' => 'b']);
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
				Console::outln("Service '$service' not found");
				return;
			}
		}

		$servicesMigrateInfo = $this->getServicesMigrateInfo($service, $model);

		if (empty($servicesMigrateInfo)) {
			Console::outln('No models need migrations', ['decor' => 'b']);
		} else {
			Console::outln('Following models need migrations:', ['decor' => 'b']);
			foreach ($servicesMigrateInfo as $info) {
				$service = $info['service'];
				Console::out('Service: ', ['decor' => 'b']);
				Console::outln($service->name);
				foreach ($info['models'] as $data) {
					Console::outln('-', $data['name']);
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
				Console::outln("Service '$service' not found");
				return;
			}
		}
		$model = $this->getArg(1);
		$servicesMigrateInfo = $this->getServicesMigrateInfo($service, $model);
		if (empty($servicesMigrateInfo)) {
			Console::outln('No models need migrations', ['decor' => 'b']);
			return;
		}
		
		$migrator = new MigrationManager();
		foreach ($servicesMigrateInfo as $info) {
			Console::out('Migration for service: ', ['decor' => 'b'] );
			Console::outln($info['service']->name, '...');

			foreach ($info['models'] as $data) {
				Console::outln('--- model:', $data['name']);
				$migrator->runModel($info['service'], $data['name'], $data['path']);
			}
		}

		Console::outln('Done', ['decor' => 'b']);
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
			Console::outln("Application configuration 'packagesMap' not found");
			return;
		}

		$name = $this->getArg(0);
		if (!$name) {
			Console::outln('You need to enter new service name');
			return;
		}
		//todo проверить корректрость имени регуляркой

		if (count($dirs) == 1) {
			$this->createServiceProcess($name, $dirs[0]);
			return;
		}

		$i = null;
		while (!is_numeric($i) || $i <= 0 || $i > count($dirs)) {
			Console::outln('Available directories for new service:', ['decor' => 'b']);
			$counter = 0;
			foreach ($dirs as $dirPath) {
				Console::out((++$counter) . '. ', ['decor' => 'b']);
				Console::outln($dirPath == '' ? '/' : $dirPath);
			}
			Console::out('q. ', ['decor' => 'b']);
			Console::outln('Quit');
			$i = Console::in('Choose number of directory: ', ['decor' => 'b']);
			if ($i == 'q') {
				Console::outln('Aborted');
				return;
			}

			//todo проверить, что такой сервис еще не существует
		}

		$this->createServiceProcess($name, $dirs[$i - 1]);
	}

	/**
	 * Создание нового модуля
	 * //todo - флаг кастомного создания
	 * */
	private function createModule() {
		// Для создания модуля нужно находиться в сервисе
		if ($this->service === null) {
			Console::outln("Modules belong to services. Enter the service");
			return;
		}

		$name = $this->getArg(0);
		if (!$name) {
			Console::outln('You need to enter new module name');
			return;
		}
		//todo проверить корректрость имени регуляркой

		// Смотрим по конфигу - какие каталоги содержат модули
		$moduleDirs = $this->service->getConfig('service.modules');
		if ($moduleDirs) {
			$moduleDirs = (array)$moduleDirs;
		}
		if (!is_array($moduleDirs) || empty($moduleDirs)) {
			Console::outln("Service configuration 'modules' not found");
			return;
		}

		// Если у сервиса только один каталог для модулей - сразу создаем там новый модуль
		if (count($moduleDirs) == 1) {
			$path = $moduleDirs[0];
			$this->createModuleProcess($this->service, $name, $path);
			return;
		}

		$i = null;
		while (!is_numeric($i) || $i < 0 || $i > count($moduleDirs)) {
			Console::outln('Available directories for new module (relative to the service directory):', ['decor' => 'b']);
			$counter = 0;
			foreach ($moduleDirs as $dirPath) {
				Console::out((++$counter) . '. ', ['decor' => 'b']);
				Console::outln($dirPath == '' ? '/' : $dirPath);
			}
			Console::out('q. ', ['decor' => 'b']);
			Console::outln('Quit');
			$i = Console::in('Choose number of directory: ', ['decor' => 'b']);
			if ($i == 'q') {
				Console::outln('Aborted');
				return;
			}

			//todo проверить, что такой модуль еще не существует
		}

		$this->createModuleProcess($this->service, $name, $moduleDirs[$i - 1]);
	}

	/**************************************************************************************************************************
	 * Методы, обслуживающие базовую работу командной строки
	 *************************************************************************************************************************/

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
				Console::outln("Argument [$i] = '$arg' is not valid. Available are: " . implode(', ', $variants));
				return false;
			}
		}
		return true;
	}

	/**
	 * Вычленяет из введенного текста имя команды и аргументы:
	 * Или так:
	 * lx-cli<app>: command arg1 arg2 "arg3 by several words"
	 * Или так:
	 * lx-cli<app>: command -k=arg1 --key="arg2 by several words"
	 * Но не перечислением и ключами одновременно (в этом случае ключи учтутся, перечисленные будут проигнорированы)
	 * @param $input string - строка консольного ввода
	 * */
	private function parseInput($input) {
		preg_match_all('/".*?"/', $input, $matches);
		$matches = $matches[0];
		$line = preg_replace('/".*?"/', '№№№', $input);
		$arr = explode(' ', $line);
		if (!empty($matches)) {
			$counter = 0;
			foreach ($arr as &$value) {
				if (strpos($value, '№№№') === false) {
					continue;
				}
				$value = str_replace('№№№', $matches[$counter++], $value);
			}
			unset($value);
		}

		$command = array_shift($arr);
		$counted = [];
		$assoc = [];

		foreach ($arr as $item) {
			if ($item{0} != '-') {
				$counted[] = $item;
				continue;
			}
			$pos = strpos($item, '=');
			$key = trim(substr($item, 0, $pos), '-');
			$value = trim(substr($item, $pos+1, strlen($item)), '"');
			$assoc[$key] = $value;
		}

		$args = empty($assoc) ? $counted : $assoc;
		return [$command, $args];
	}

	/**
	 * Проверяет соответствует ли команда какой-то категории
	 * @param $command string - команда, уже вычлененная из строки консольного ввода
	 * */
	private function checkCommand($command, $key) {
		$keywords = self::COMMANDS[$key];
		if (is_array($keywords)) {
			return (array_search($command, $keywords) !== false);
		}
		return $command == $keywords;
	}

	/**
	 * Пытается дополнить введенную команду:
	 * - находит ближайшее общее если подходящих команд несколько
	 * - помимо общего возвращает список подходящих команд
	 * @param $text string - строка, которую требуется дополнить
	 * */
	private function tryFinishCommand($text) {
		if ($text{0} == '\\') {
			return false;
		}

		$len = mb_strlen($text);
		if ($len == 0) {
			return false;
		}

		$matches = [];

		foreach (self::COMMANDS as $keywords) {
			foreach ((array)$keywords as $command) {
				if ($command != $text && preg_match('/^'. $text .'/', $command)) {
					$matches[] = $command;
				}
			}
		}

		if (empty($matches)) {
			return false;
		}

		$commonPart = $text;
		$i = $len;
		while (true) {
			$latterMatch = true;
			if ($i >= mb_strlen($matches[0])) break;
			$latter = $matches[0]{$i};
			foreach ($matches as $command) {
				if ($i >= mb_strlen($command)) break(2);
				if ($latter != $command{$i}) break(2);
			}
			$commonPart .= $latter;
			$i++;
		}

		return [
			'common' => $commonPart,
			'matches' => $matches
		];
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
