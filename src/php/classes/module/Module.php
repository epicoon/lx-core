<?php

namespace lx;

/*
* * *  1. Доступ к модулям  * * *
	public static function create($service, $moduleName, $modulePath)

* * *  2. Сеттеры  * * *
	public function setMain($bool)
	public function setConfig($name, $value)
	public function setScreenModes($arr)
	public function addParams($data)
	public function preJs($code)
	public function postJs($code)
	public function setHandler($name, $handler)
	public function script($name, $onSuccess=0, $onError=0, $loc='head')

* * *  3. Геттеры  * * *
	public function __get($field)
	public function getService()
	public function getPath()
	public function getFilePath($fileName)
	public function getFile($name)
	public function findFile($name)
	public function getConfig($key = null)
	public function getImageRoute($name)
	public function extractScripts()

* * *  4. Методы формирования ответов  * * *
	protected function scripts()
	protected function ajaxResponse($data)
	private function sendAjaxResponse($requestData)
	private function ajaxResponseByRespondent($respondentName, $data)

* * *  6. Скрытое создание модуля  * * *
	protected function __construct($data)
	protected function init()

* * *  7. Информация необходимая для билдера  * * *
	public function getSelfInfo()
	public function getPreJs()
	public function getPostJs()
	public function getScripts()
	public function getCss()
*/
class Module {
	public
		$title = null,   // Заголовок страницы модуля (для использования модуля во фрэйме не актуально)
		$data = null,    // Данные, которые будут реплицированы на стороне клиента
		$params = null;  // Параметры, используемые модулем на стороне сервера

	protected
		$service = null,
		$_name = '',
		$config = [];    // Собственные конфиги модуля, определенные в конфигурационном файле в его каталоге

	private
		$_directory = null,      // Каталог, связанный с модулем
		$_conductor = null,      // Проводник по структуре модуля
		$isMain = false,         // Главный модуль - который рендерился с ядром, при начальной загрузке страницы
		
		$screenModes = [],

		// Все варианты js-кода
		$jsBootstrap = null,
		$preJs = [],
		$js = null,
		$postJs = [],
		$handlersList = [],
		$_scripts = [];


	//=========================================================================================================================
	/* * *  1. Доступ к модулям  * * */

	/**
	 * //todo - модуль должен вызываться всегда опосредованно через сервис. Может закрыть этот метод?
	 * */
	public static function create($service, $moduleName, $modulePath) {
		$dir = new ModuleDirectory($modulePath);
		if (!$dir->exists()) {
			return null;
		}

		$configFile = $dir->getConfigFile();

		$config = $configFile !== null
			? $configFile->get()
			: [];

		$moduleClass = isset($config['class']) ? $config['class'] : self::class;
		unset($config['class']);

		$module = new $moduleClass([
			'service' => $service,
			'name' => $moduleName,
			'directory' => $dir,
			'config' => $config,
		]);

		return $module;
	}


	//=========================================================================================================================
	/* * *  2. Сеттеры  * * */

	/**
	 * Главный модуль - который рендерился с ядром, при начальной загрузке страницы
	 * */
	public function setMain($bool) {
		$this->isMain = $bool;
	}

	/**
	 *
	 * */
	public function setConfig($name, $value) {
		$this->config[$name] = $value;
	}

	/**
	 *
	 * */
	public function setScreenModes($arr) {
		foreach ($arr as &$value) {
			if ($value == INF) $value = 'inf';
		}
		$this->screenModes = $arr;
	}

	/**
	 * Добавить сразу несколько параметров при помощи массива
	 * */
	public function addParams($data) {
		foreach ($data as $key => $value) {
			$this->params->$key = $value;
		}
	}

	public function preJs($code) {
		$this->preJs[] = $code;
	}

	public function postJs($code) {
		$this->postJs[] = $code;
	}

	public function setHandler($name, $handler) {
		$this->handlersList[$name] = JsCompiler::compileCodeInString($handler);
	}

	/**
	 *
	 * */
	public function script($name, $onSuccess=0, $onError=0, $loc='head') {
		if (is_array($name)) {
			return $this->script(
				$name['script'],
				$name['success'],
				$name['error'],
				isset($name['loc']) ? $name['loc'] : 'head'
			);
		}

		$scriptPath = $this->conductor->getScriptPath($name);

		if (!isset($this->_scripts[$loc]))
			$this->_scripts[$loc] = [];

		if (!array_search($scriptPath, $this->_scripts[$loc]))
			$this->_scripts[$loc][] = (!$onSuccess && !$onError)
				? $scriptPath
				: [$scriptPath, $onSuccess, $onError];
	}


	//=========================================================================================================================
	/* * *  3. Геттеры  * * */

	public function __get($field) {
		if ($field == 'name') return $this->_name;
		if ($field == 'conductor') return $this->_conductor;
		if ($field == 'directory') return $this->_directory;
		return null;
	}

	public function getService() {
		return $this->service;
	}

	/**
	 * Возвращает путь к директории, являющейся корневой для модуля
	 * */
	public function getPath() {
		return $this->conductor->getModulePath();
	}

	/**
	 * Получить имя файла с учетом использования алиасов (модуля и приложения)
	 * */
	public function getFilePath($fileName) {
		return $this->conductor->getFullPath($fileName);
	}

	/**
	 * Получить файл с учетом использования алиасов (модуля и приложения)
	 * */
	public function getFile($name) {
		return $this->conductor->getFile($name);
	}

	/**
	 * Поиск файла в модуле (и только в модуле)
	 * */
	public function findFile($name) {
		return $this->_directory->find($name);
	}

	/**
	 *
	 * */
	public function getConfig($key = null) {
		if ($key === null) return $this->config;
		if (!isset($this->config[$key])) return null;
		return $this->config[$key];
	}

	/**
	 *
	 * */
	public function getImageRoute($name) {
		return $this->conductor->getImageRoute($name);
	}

	/**
	 * Метод для статической сборки - возвращает скрипты модуля, удаляя информацию в самом модуле (чтобы подключить их на стороне сервера,
	 * а инфа о необходимости подключения на клиента уже не попала)
	 * Соответсвенно можно этим методом получить скрипты только единожды.
	 * */
	public function extractScripts() {
		$result = $this->_scripts;
		$this->_scripts = true;
		return $result + $this->scripts();
	}


	//=========================================================================================================================
	/* * *  4. Методы формирования ответов  * * */

	/**
	 * //todo оно вообще надо?
	 * Можно переопределить у потомка, чтобы задать подключаемые скрипты
	 * */
	protected function scripts() {
		return [];
	}

	/**
	 * Метод, который можно переопределить в потомке для формирования ответов без респондентов
	 * */
	protected function ajaxResponse($data) {
		return false;
	}

	/**
	 * Формирование ответа для AJAX-запроса
	 * */
	private function sendAjaxResponse($requestData) {
		// Вопрошаем к конкретному респонденту
		if (isset($requestData['__respondent__'])) {
			$respondent = $requestData['__respondent__'];
			$data = isset($requestData['data']) ? $requestData['data'] : null;

			return $this->ajaxResponseByRespondent($respondent, $data);
		}

		// Отсылаем на переопределяемый метод, где вручную должен разруливаться запрос
		return $this->ajaxResponse($requestData);
	}

	/**
	 * Формирование ответа на Ajax-запрос с помощью класса-ответчика (фактически контроллер), алгоритм:
	 * 1. Получаем $respondentName => 'RespondentName/methodName'
	 * 2. Дополняем имя респондента нэймспэйсом (из конфига модуля)
	 * 3. Создаем экземпляр респондента и вызываем нужный метод (можно передать в него какие-то данные)
	 * 4. Результат выполнения метода возвращаем как данные для ответа
	 * */
	private function ajaxResponseByRespondent($respondentName, $data) {
		// Попробуем найти респондента
		$respInfo = explode('/', $respondentName);
		$respondent = $this->conductor->findRespondent($respInfo[0]);

		if (!$respondent) {
			return "Respondent {$respInfo[0]} not found";
		}

		if (!method_exists($respondent, $respInfo[1])) {
			return "Respondent method {$respInfo[1]} not found";
		}

		$result = (is_array($data))
			? \call_user_func_array([$respondent, $respInfo[1]], $data)
			: $respondent->{$respInfo[1]};

		// Если ответ при формировании ничего не вернул - считаем это успехом
		if ($result === null) $result = true;

		return $result;
	}


	//=========================================================================================================================
	/* * *  6. Скрытое создание модуля  * * */

	/**
	 * Защищенный конструктор - модули создаются только фабричным методом
	 * */
	protected function __construct($data) {
		$this->service = $data['service'];

		$this->_directory = $data['directory'];
		$this->_conductor = new ModuleConductor($this);

		$this->_name = $this->service->getID() . ':' . $data['name'];
		$this->data = new DataObject();
		$this->params = new DataObject();

		// Конфиги
		$config = $data['config'];

		// Общие настройки
		$commonConfig = \lx::getDefaultModuleConfig();

		// Дефолтные настройки
		foreach ($commonConfig as $key => $value) {
			if (!array_key_exists($key, $config)) {
				$config[$key] = $commonConfig[$key];
			}
		}

		// Импорт общих для всех модулей алиасов, если требуются
		if ($config['useCommonAliases'] === true) {
			if (!isset($config['aliases'])) $config['aliases'] = [];
			$config['aliases'] += $config['commonAliases'];
		}

		$this->config = $config;

		$this->init();
	}

	/**
	 * Метод для переопределения у потомков - инициализация необходимых полей при создании модуля
	 * Переопределять конструктор неудобно (надо вызывать в нем родительский, помнить какие надо параметры принять и пробросить родительскому...)
	 * Этот метод избавляет от необходимости переопределять конструктор
	 * */
	protected function init() {

	}


	//=========================================================================================================================
	/* * *  7. Информация необходимая для билдера  * * */

	/**
	 * Сборка информации непосредственно о самом модуле
	 * @return array : $info = [
	 *	'name'
	 *	'main'
	 *	'data'
	 *	'images'
	 *	'screenModes'
	 * ]
	 * */
	public function getSelfInfo() {
		$config = $this->config;
		$info = [
			'name' => $this->_name
		];

		// Если модуль собирался при загрузке страницы
		if ($this->isMain) $info['main'] = 1;

		// Набор произвольных данных
		$data = $this->data->getProperties();
		if (!empty($data)) $info['data'] = $data;

		// Источник изображений модуля
		if (isset($config['images']))
			$info['images'] = $this->conductor->getImagePathInSite();

		// Режимы отображения
		if (!empty($this->screenModes))
			$info['screenModes'] = $this->screenModes;

		// Хэндлеры событий модуля
		if (!empty($this->handlersList))
			$info['handlers'] = $this->handlersList;

		return $info;
	}

	/**
	 *
	 * */
	public function getPreJs() {
		return $this->preJs;
	}

	/**
	 *
	 * */
	public function getPostJs() {
		return $this->postJs;
	}

	/**
	 * //todo внимательнее такое объединение протестить
	 * */
	public function getScripts() {
		return $this->_scripts + $this->scripts();
	}

	/**
	 * //todo еще, возможно по аналогии с script() надо метод, в котором в дополнение к конфигам можно будет указывать css-файлы
	 * */
	public function getCss() {
		return $this->conductor->getCssAssets();
	}
}
