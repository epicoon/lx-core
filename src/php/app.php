<?php


/*
Сделать с ближайшее время!!!

Сервисы
- сервис авторизации
- сервис CMS

Модели
- генерация миграций
- yaml-модели под MySQL
- внешние ключи

//todo - заменить по возможности for() на foreach() - он работает в 2 раза быстрее. Для этого надо для коллекции и вектора запилить итераторы
*/



/*
1. Взаимодействие с различными настройками приложения
	public static function getConfig($param = null)
	public static function getDefaultServiceConfig()
	public static function getDefaultModuleConfig()
	public static function getSettings()
	public static function getSetting($name)
	public static function addSetting($name, $value)

2. Методы доступа к модулям
	public static function getService($name)
	public static function getModule($serviceName, $moduleName = null)
	public static function getModulePath($serviceName, $moduleName = null)

3. Вспомогательные и информационные методы
	public static function isMode($mode)
	public static function sitePath()

4. Запуск приложения
	public static function run()
	public static function runConsole($argv)

5. Сборка ответа
	private static function compileJsCore()
	private static function compileJsBootstrap()
	private static function compileJsMain()
	private static function ajaxResponse()
	private static function serviceAjaxResponse()
	private static function moduleAjaxResponse()
	private static function toJS($str)

6. Базовая инициализация
	public static function baseInitialisation()
	public static function consoleInitialisation()
	public static function installInitialisation()
	private static function retrieveSitePath()
	private static function loadConfig()
*/
class lx {
	const APP_TYPE_SIMPLE = 'simple';
	const APP_TYPE_COMPOSER_PACKAGE = 'composer-package';

	const LEFT = 1;
	const CENTER = 2;
	const WIDTH = 2;
	const RIGHT = 3;
	const JUSTIFY = 4;
	const TOP = 5;
	const MIDDLE = 6;
	const HEIGHT = 6;
	const BOTTOM = 7;
	const VERTICAL = 1;
	const HORIZONTAL = 2;

	const POSTUNPACK_TYPE_IMMEDIATLY = 1;
	const POSTUNPACK_TYPE_FIRST_DISPLAY = 2;
	const POSTUNPACK_TYPE_ALL_DISPLAY = 3;

	/**
	 * Системные компоненты
	 * */
	public static
		$dialog,
		$conductor,
		$data;

	private static
		$type = '',
		$defaultServiceConfig = null,
		$defaultModuleConfig = null;

	/**
	 * Данные, которые будут отправлены клиентскому lx
	 * */
	private static
		$settings = [
			'unpackType' => \lx::POSTUNPACK_TYPE_FIRST_DISPLAY,
			'treeSeparator' => '/',
		];

	/**
	 * Закэшированные системные данные
	 * */
	private static
		$_site = '',
		$_config = null;


	private static $alertStack = [];

	public static function alert($data) {
		self::$alertStack[] = $data;
	}

	public static function getAlerts() {
		if (empty(self::$alertStack)) {
			return null;
		}
		return 'alert:' . json_encode(self::$alertStack);
	}


	//=========================================================================================================================
 	/* * *  1. Взаимодействие с различными настройками приложения  * * */

 	/**
 	 *
 	 * */
 	public static function getType() {
 		return self::$type;
 	}

	/**
	 * Получить конфиги приложения, или конкретный конфиг
	 * */
	public static function getConfig($param = null) {
		if (self::$_config === null) self::loadConfig();
		if ($param === null) return self::$_config;
		if (array_key_exists($param, self::$_config)) return self::$_config[$param];
		return null;
	}

	/**
	 *
	 * */
	public static function getDefaultServiceConfig() {
		if (self::$defaultServiceConfig === null) {
			self::$defaultServiceConfig = (new lx\File(self::$conductor->defaultServiceConfig))->load();
		}
		return self::$defaultServiceConfig;
	}

	/**
	 *
	 * */
	public static function getDefaultModuleConfig() {
		if (self::$defaultModuleConfig === null) {
			self::$defaultModuleConfig = (new lx\File(self::$conductor->defaultModuleConfig))->load();
		}
		return self::$defaultModuleConfig;
	}

	/**
	 * Получение всех настроек
	 * */
	public static function getSettings() {
		return self::$settings;
	}

	/**
	 * Получение конкретной настройки
	 * */
	public static function getSetting($name) {
		if (array_key_exists($name, self::$settings))
			return self::$settings[$name];
		return null;
	}

	/**
	 * Добавить поле настроек, которое отправится в клиентский lx
	 * */
	public static function addSetting($name, $value) {
		self::$settings[$name] = $value;
	}


	//=========================================================================================================================
 	/* * *  2. Методы доступа к структурам  * * */

	/**
	 * //todo - надо добавить псевдонимы для сервисов
	 * */
	public static function getService($name) {
		return lx\Service::create($name);
	}

	/**
	 * Получение модуля из сервиса
	 * */
	public static function getModule($serviceName, $moduleName = null) {
		if ($moduleName === null) {
			$arr = explode(':', $serviceName);
			$serviceName = $arr[0];
			$moduleName = $arr[1];
		}

		return self::getService($serviceName)->getModule($moduleName);
	}

	/**
	 * Получение пути к модулю
	 * */
	public static function getModulePath($serviceName, $moduleName = null) {
		if ($moduleName === null) {
			$arr = explode(':', $serviceName);
			$serviceName = $arr[0];
			$moduleName = $arr[1];
		}

		return self::getService($serviceName)->conductor->getModulePath($moduleName);
	}


	//=========================================================================================================================
 	/* * *  3. Вспомогательные и информационные методы  * * */

 	/**
 	 * Проверяет текущий режим работы приложения
 	 * */
	public static function isMode($mode) {
		$currentMode = self::getConfig('mode');
		if (!$currentMode) return false;

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
	 * Абсолютный путь к директории проекта
	 * */
	public static function sitePath() {
		return self::$_site;
	}


	//=========================================================================================================================
 	/* * *  4. Запуск приложения  * * */

	/**
	 * Запуск формирования ответа клиенту
	 * */
	public static function run() {
		self::baseInitialisation();

		// Если удастся автоматически ответить на AJAX-запрос, то хорошо (при наличии заголовка lx-type)
		if (self::$dialog->isAjax()) {
			if (self::ajaxResponse()) {
				return;
			}
		}

		$routerData = self::getConfig('router');
		if (!$routerData) {
			//todo - возможно будет работать дефолтный роутер
			require_once(__DIR__ . '/stdResponses/404.php');
			return;
		}

		$router = null;
		if (isset($routerData['type'])) {
			switch ($routerData['type']) {
				case 'map':
					$data = null;
					if (isset($routerData['path'])) {
						$path = self::$conductor->getFullPath($routerData['path']);
						$file = new lx\ConfigFile($path);
						if ($file->exists()) $data = $file->get();
					} elseif (isset($routerData['routes'])) {
						$data = $routerData['routes'];
					}
					if ($data) {
						$router = new lx\Router();
						$router->setMap($data);
					}
					break;
				case 'class':
					if (isset($routerData['name']) && lx\ClassHelper::exists($routerData['name'])) {
						$router = new $routerData['name']();
					}
					break;
			}
		}

		$response = false;
		if ($router !== null) $response = $router->route();
		if ($response === false) {
			require_once(__DIR__ . '/stdResponses/404.php');
			return;
		}

		$response->send();
	}

	/**
	 * Запуск консольного приложения
	 * */
	public static function runConsole($argv) {
		self::consoleInitialisation();

		$command = array_pop($argv);
		switch ($command) {
			case 'reset-autoload-map':
				(new lx\AutoloadMapBuilder())->createCommonAutoloadMap();
				lx\Console::outln('Done');
				break;

			/*
			Выводит список пакетов с поясняющей записью (description)
			//todo - перенести функционал в Cli
			*/
			case 'pkt':
				$list = lx\Autoloader::getInstance()->getPackagesList();
				foreach ($list as $i => $data) {
					$data = lx\DataObject::create($data);
					lx\Console::out( ($i+1) . '. ' . $data->name, ['decor' => 'b'] );
					lx\Console::outln( ': ' . $data->description );
				}
				break;


			case 'cli':
				(new lx\Cli())->run();
				break;
			default:
				/*
				//todo - надо ли вообще делать на таком уровне обработку запросов?
				Можно сделать так, чтобы консольные команды для сервисов работали только из-под CLI
				Зашел в CLI, зашел в модуль, работаешь с ним через его команды
				*/
				break;
		}
	}

	//=========================================================================================================================
 	/* * *  5. Сборка ответа  * * */

	/**
	 * Собирает js-ядро
	 * */
	private static function compileJsCore() {
		$path = self::$conductor->jsCore;
		$code = file_get_contents($path);
		$code = lx\JsCompiler::compileCode($code, $path);
		return $code;
	}

	/**
	 * Глобальный js-код, выполняемый до разворачивания корневого модуля
	 * */
	private static function compileJsBootstrap() {
		$path = self::getConfig('jsBootstrap');
		if ($path === null || $path === false) return '';

		$path = self::$conductor->decodeAlias($path);
		if (!file_exists($path)) return '';

		$code = file_get_contents($path);
		$code = lx\JsCompiler::compileCode($code, $path);
		return $code;
	}

	/**
	 * Глобальный js-код, выполняемый после разворачивания корневого модуля
	 * */
	private static function compileJsMain() {
		$path = self::getConfig('jsMain');
		if ($path === null || $path === false) return '';

		$path = self::$conductor->decodeAlias($path);
		if (!file_exists($path)) return '';

		$code = file_get_contents($path);
		$code = lx\JsCompiler::compileCode($code, $path);
		return $code;
	}

	/**
	 * Формирование ajax-ответа
	 * */
	private static function ajaxResponse() {
		switch (self::$dialog->header('lx-type')) {
			// Ajax-запрос, произошедший явно в контексте какого-то модуля
			case 'module': self::moduleAjaxResponse(); break;
			
			// Служебный (системный) ajax-запрос
			case 'service': self::serviceAjaxResponse(); break;

			default:
				// //todo - логика переделывается, пока смесь API с AJAX заморожена
				// // Для общего случая попробуем найти модуль из урла и ответить в стиле API
				// $module = self::getModule( self::$dialog->route() );
				// if ($module) {
				// 	$apiTry = (new lx\ModuleBuilder($module))->callPrivateModuleMethod('sendApiResponse', ['data' => self::$dialog->data()]);
				// 	if ($apiTry) return;
				// }
				// self::$dialog->send(false);
				return false;
		}

		return true;
	}

	/**
	 * Служебные lx-запросы
	 * */
	private static function serviceAjaxResponse() {
		// Ajax-запрос на дозагрузку виджетов
		if (self::$dialog->route() == 'get-widgets') {
			$data = self::$dialog->params();
			$code = lx\WidgetHelper::getWidgetsCode($data);
			self::$dialog->send(
				$code
			);
		}
	}

	/**
	 * Формирование ajax-ответа для модуля
	 * */
	private static function moduleAjaxResponse() {
		$moduleName = self::$dialog->header('lx-module');
		if ($moduleName === null) {
			throw new Exception('Module-ajax-request without module!', 400);
		}

		$arr = explode(':', $moduleName);
		$service = self::getService($arr[0]);

		$module = null;
		if ($service) {
			$module = $service->getModule($arr[1]);
		}

		if ($module === null) {
			self::$dialog->send("Module '$moduleName' not found");
			return;
		}

		$result = lx\ClassHelper::call($module, 'sendAjaxResponse', [\lx::$dialog->params()]);
		$alerts = self::getAlerts();
		if ($alerts) {
			self::$dialog->send($alerts);
		} else {
			self::$dialog->send($result);
		}
	}

	/**
	 * При генерации напрямую встраиваемого js-кода для передачи объектов из php в js
	 * */
	private static function toJS($str) {
		if (!is_string($str)) $str = json_encode($str);

		//todo <json-дрочево>!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
		// курить в этом направлении htmlentities($str, ENT_QUOTES); Это еще и от XSS полезно
		// Экранирование слэшей
		$str = str_replace('\\', '\\\\', $str);
		// Экранирование одинарных кавычек
		$str = str_replace('\'', '\\\'', $str);
		// Экранирование двойных кавычек
		$str = str_replace('"', '\"', $str);

		return 'JSON.parse(\'' . $str . '\')';
	}


	//=========================================================================================================================
 	/* * *  6. Базовая инициализация  * * */

	/**
	 * Запуск приложения (и с AJAX и без), базовая инициализация
	 * */
	public static function baseInitialisation() {
		self::retrieveSitePath();

		require_once(__DIR__ . '/classes/system/autoload/Autoloader.php');
		$autoloader = lx\Autoloader::getInstance();
		$autoloader->init();

		$innerConfig = require(lx\Conductor::innerConfigPath());
		if (isset($innerConfig['type'])) {
			self::$type = $innerConfig['type'];
		}

		self::$conductor = new lx\Conductor(self::$_site);
		$aliases = self::getConfig('aliases');
		if (!$aliases) $aliases = [];
		self::$conductor->setAliases($aliases);

		self::$data = new lx\DataObject();
		self::$dialog = new lx\Dialog();
	}

	/**
	 * Запуск консольного приложения
	 * */
	public static function consoleInitialisation() {
		self::$_site = dirname(__DIR__, 5);

		require_once(__DIR__ . '/classes/system/autoload/Autoloader.php');
		$autoloader = lx\Autoloader::getInstance();
		$autoloader->init();

		$innerConfig = require(lx\Conductor::innerConfigPath());
		if (isset($innerConfig['type'])) {
			self::$type = $innerConfig['type'];
		}

		//todo - куча всякого консольного - свои конфиги и т.д.??
	
		self::$conductor = new lx\Conductor(self::$_site);
		$aliases = self::getConfig('aliases');
		if (!$aliases) $aliases = [];
		self::$conductor->setAliases($aliases);
	}

	/**
	 *
	 * */
	public static function installInitialisation() {
		self::$type = self::APP_TYPE_COMPOSER_PACKAGE;
		self::$_site = dirname(__DIR__, 5);

		require_once(__DIR__ . '/classes/system/autoload/Autoloader.php');
		$autoloader = lx\Autoloader::getInstance();
		$autoloader->init();
		self::$conductor = new lx\Conductor(self::$_site);
	}

	/**
	 * Записывает абсолютный адрес сайта
	 * */
	private static function retrieveSitePath() {
		if (self::$type == self::APP_TYPE_COMPOSER_PACKAGE) {
			self::$_site = dirname(__DIR__, 5);
		} else {
			self::$_site = isset($_SERVER['DOCUMENT_ROOT'])
				? $_SERVER['DOCUMENT_ROOT']
				: dirname(__DIR__, 4);
		}
	}

	/**
	 * Загрузка основных конфигов приложения
	 * */
	private static function loadConfig() {
		$path = self::$conductor->appConfig;
		self::$_config = require($path);
	}
}
