<?php

namespace lx;

/*
* * *  1. Основные  * * *
	public static function create($service, $pluginName, $pluginPath)
	public function beforeCompile()
	public function afterCompile()

* * *  2. Сеттеры  * * *
	public function setConfig($name, $value)
	public function setScreenModes($arr)
	public function addRenderParams($params)
	public function preJs($code)
	public function postJs($code)
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
	public function getWidgetBasicCss($widgetClass)
	public function widgetBasicCssList()
	public function getRespondent($name)
	public function extractScripts()
	public function prototypePlugin()
	public function prototypeService()
	public function getResponseSource($requestData)

* * *  4. Методы формирования ответов  * * *
	protected function scripts()
	protected function ajaxResponse($data)
	private function ajaxResponseByRespondent($respondentName, $data)

* * *  6. Скрытое создание плагина  * * *
	protected function __construct($data)
	protected function init()
	protected function beforeAddParams($params)
	protected function afterAddParams($params)

* * *  7. Информация необходимая для билдера  * * *
	public function getSelfInfo()
	public function getPreJs()
	public function getPostJs()
	public function getScripts()
	public function getCss()
*/
class Plugin extends Source implements FusionInterface {
	use FusionTrait;

	const CACHE_NONE = 'none';
	const CACHE_ON = 'on';
	const CACHE_STRICT = 'strict';
	const CACHE_BUILD = 'build';
	const CACHE_SMART = 'smart';

	public
		$title = null,   // Заголовок страницы плагина (для использования плагина во фрэйме не актуально)
		$icon = null,
		$renderParams = null,  // Параметры, используемые плагином на стороне сервера
		$clientParams = null;  // Данные, которые будут реплицированы на стороне клиента

	protected
		$service = null,
		$_name = '',
		$_prototype = null,
		$config = [];    // Собственные конфиги плагина, определенные в конфигурационном файле в его каталоге

	private
		$_directory = null,  // Каталог, связанный с плагином
		$_conductor = null,  // Проводник по структуре плагина
		$anchor,
		$rootSnippetKey,
		
		$screenModes = [],

		// Зависимости (н-р от модулей)
		$dependencies = [],

		// Все варианты js-кода
		$jsBootstrap = null,
		$preJs = [],
		$js = null,
		$postJs = [],
		$_scripts = [];


	//=========================================================================================================================
	/* * *  1. Основные  * * */

	public function __construct($data) {
		parent::__construct($data);

		$this->service = $data['service'];

		$this->_directory = $data['directory'];
		$this->_conductor = new PluginConductor($this);

		$this->_name = $this->service->getID() . ':' . $data['name'];
		$this->clientParams = new DataObject();
		$this->renderParams = new DataObject();
		$this->anchor = '_root_';

		if (isset($data['prototype'])) {
			$this->_prototype = $data['prototype'];
		}

		// Конфиги
		$config = $data['config'];

		// Общие настройки
		$commonConfig = $this->app->getDefaultPluginConfig();
		ConfigHelper::preparePluginConfig($commonConfig, $config);

		// Инъекция конфигов
		$injections = $this->app->getConfig('configInjection');
		ConfigHelper::pluginInject($this->name, $this->prototype, $injections, $config);

		$this->config = $config;
		$this->initFusionComponents($this->getConfig('components'));

		$this->init();
	}

	/**
	 * Можно собрать плагин для сервиса по пути
	 * */
	public static function create($service, $pluginName, $pluginPath, $prototype = null) {
		$dir = new PluginDirectory($pluginPath);
		if (!$dir->exists()) {
			return null;
		}

		$configFile = $dir->getConfigFile();

		$config = $configFile !== null
			? $configFile->get()
			: [];

		$pluginClass = isset($config['class']) ? $config['class'] : self::class;
		unset($config['class']);

		$data = [
			'service' => $service,
			'name' => $pluginName,
			'directory' => $dir,
			'config' => $config,
		];

		if ($prototype) {
			$data['prototype'] = $prototype;
		}

		$plugin = \lx::$app->diProcessor->create($pluginClass, $data);

		return $plugin;
	}

	public function build()
	{
		$builder = new PluginBuildContext($this);
		return $builder->build();
	}

	public function beforeCompile() {}
	public function afterCompile() {}
	public function beforeSending() {}
	public function beforeSuccessfulSending() {}
	public function beforeFailedSending() {}
	public function afterSuccessfulSending() {}
	public function afterFailedSending() {}
	public function afterSending() {}


	//=========================================================================================================================
	/* * *  2. Сеттеры  * * */

	public function setConfig($name, $value) {
		$this->config[$name] = $value;
	}

	public function setAnchor($anchor) {
		$this->anchor = $anchor;
	}

	public function getAnchor() {
		return $this->anchor;
	}

	public function setRootSnippetKey($key) {
		$this->rootSnippetKey = $key;
	}

	public function getRootSnippetKey() {
		return $this->rootSnippetKey;
	}

	public function setScreenModes($arr) {
		foreach ($arr as &$value) {
			if ($value == INF) $value = 'inf';
		}
		$this->screenModes = $arr;
	}

	/**
	 * Добавить сразу несколько параметров при помощи массива
	 * */
	public function addRenderParams($params) {
		$params = $this->beforeAddParams($params);
		if ($params === false) {
			return;
		}

		foreach ($params as $key => $value) {
			$this->renderParams->$key = $value;
		}

		$this->afterAddParams($params);
	}

	public function setDependencies($list) {
		$this->dependencies = $list;
	}

	public function getModuleDependencies() {
		if (isset($this->dependencies['modules'])) {
			return $this->dependencies['modules'];
		}

		return [];
	}

	public function preJs($code) {
		$this->preJs[] = $code;
	}

	public function postJs($code) {
		$this->postJs[] = $code;
	}

	/**
	 *
	 * */
	public function script($name, $onSuccess=0, $onError=0) {
		if (is_array($name)) {
			return $this->script(
				$name['script'],
				$name['success'],
				$name['error']
			);
		}

		$scriptPath = $this->conductor->getScriptPath($name);

		if (!array_search($scriptPath, $this->_scripts)) {
			$this->_scripts[] = (!$onSuccess && !$onError)
				? $scriptPath
				: [$scriptPath, $onSuccess, $onError];
		}
	}

	//=========================================================================================================================
	/* * *  3. Геттеры  * * */

	public function __get($field) {
		if ($field == 'name') return $this->_name;
		if ($field == 'prototype') return $this->_prototype;
		if ($field == 'conductor') return $this->_conductor;
		if ($field == 'directory') return $this->_directory;

		$component = $this->getFusionComponent($field);
		if ($component !== null) {
			return $component;
		}

		return parent::__get($field);
	}
	
	public function getBuildData() {
		$renderParams = $this->renderParams->getProperties();
		$clientParams = $this->clientParams->getProperties();
		
		$result = [
			'name' => $this->_name,
			'path' => $this->getPath(),
			'images' => $this->conductor->getImagePathInSite(),
			'serviceName' => $this->service->name,
		];

		if (!empty($renderParams)) $result['renderParams'] = $renderParams;
		if (!empty($clientParams)) $result['clientParams'] = $clientParams;

		$widgetBasicCssList = $this->widgetBasicCssList();
		if (!empty($widgetBasicCssList)) $result['widgetBasicCss'] = $widgetBasicCssList;

		return $result;
	}

	public function applyBuildData($data) {
		if (isset($data['title'])) {
			$this->title = $data['title'];
		}

		if (isset($data['icon'])) {
			$this->icon = $data['icon'];
		}

		$this->clientParams->setProperties($data['clientParams']);
		foreach ($data['preJs'] as $code) {
			$this->preJs($code);
		}
		foreach ($data['postJs'] as $code) {
			$this->postJs($code);
		}
	}

	public function getService() {
		return $this->service;
	}

	/**
	 * Возвращает путь к директории, являющейся корневой для плагина
	 * */
	public function getPath() {
		return $this->conductor->getPath();
	}

	/**
	 * Получить имя файла с учетом использования алиасов (плагина и приложения)
	 * */
	public function getFilePath($fileName) {
		return $this->conductor->getFullPath($fileName);
	}

	/**
	 * Получить файл с учетом использования алиасов (плагина и приложения)
	 * */
	public function getFile($name) {
		return $this->conductor->getFile($name);
	}

	/**
	 * Поиск файла в плагине (и только в плагине)
	 * */
	public function findFile($name) {
		return $this->_directory->find($name);
	}

	public function getFusionComponentsDefaultConfig()
	{
		return [
			'i18nMap' => I18nPluginMap::class,
		];
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

	public function getWidgetBasicCss($widgetClass) {
		$list = $this->widgetBasicCssList();
		if (array_key_exists($widgetClass, $list)) {
			return $list[$widgetClass];
		}

		return false;
	}

	public function widgetBasicCssList() {
		return [];
	}

	/**
	 *
	 * */
	public function getRespondent($name) {
		$respondent = $this->conductor->findRespondent($name);

		if (!$respondent) {
			return null;
		}

		return $respondent;
	}

	/**
	 * Метод для статической сборки - возвращает скрипты плагина, удаляя информацию в самом плагине (чтобы подключить их на стороне сервера,
	 * а инфа о необходимости подключения на клиента уже не попала)
	 * Соответсвенно можно этим методом получить скрипты только единожды.
	 * */
	public function extractScripts() {
		$result = $this->_scripts;
		$this->_scripts = true;
		return $result + $this->scripts();
	}

	public function getPrototypePlugin() {
		if ($this->prototype) {
			return $this->app->getPlugin($this->prototype);
		}

		return null;
	}

	public function getPrototypeService() {
		if ($this->prototype) {
			$serviceName = explode(':', $this->prototype)[0];
			return $this->app->getService($serviceName);
		}

		return null;
	}

	public function getRootPlugin()
	{
		if ( ! $this->prototype) {
			return $this;
		}
		
		return $this->app->getPlugin($this->prototype)->getRootPlugin();
	}

	public function getRootService()
	{
		if ( ! $this->prototype) {
			return $this->getService();
		}

		return $this->app->getPlugin($this->prototype)->getRootPlugin()->getService();
	}

	/**
	 * Формирование ответа для AJAX-запроса
	 * */
	public function getResponseSource($respondent, $data) {
		if (!isset($data['params']) || !isset($data['data'])) {
			//todo логировать?
			return false;
		}

		$this->clientParams->setProperties($data['params']);
		$requestData = $data['data'];

		// Вопрошаем к конкретному респонденту
		if ($respondent) {
			return $this->ajaxResponseByRespondent($respondent, $requestData);
		}

		// Отсылаем на переопределяемый метод, где вручную должен разруливаться запрос
		return new SourceContext([
			'object' => $this,
			'method' => 'ajaxResponse',
			'params' => [$requestData],
		]);
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
	 * Формирование ответа на Ajax-запрос с помощью класса-ответчика (фактически контроллер), алгоритм:
	 * 1. Получаем $respondentName => 'RespondentName/methodName'
	 * 2. Дополняем имя респондента нэймспэйсом (из конфига плагина)
	 * 3. Создаем экземпляр респондента и вызываем нужный метод (можно передать в него какие-то данные)
	 * 4. Результат выполнения метода возвращаем как данные для ответа
	 * */
	private function ajaxResponseByRespondent($respondentName, $respondentParams) {
		// Попробуем найти респондента
		$respInfo = preg_split('/[^\w\d_]/', $respondentName);
		$respondent = $this->conductor->findRespondent($respInfo[0]);

		if (!$respondent) {
			//todo логировать? "Respondent {$respInfo[0]} not found";
			return false;
		}

		$methodName = $respInfo[1];
		if (!method_exists($respondent, $methodName)) {
			//todo логировать? "Respondent method {$methodName} not found";
			return false;
		}

		return new SourceContext([
			'object' => $respondent,
			'method' => $methodName,
			'params' => $respondentParams,
		]);
	}


	//=========================================================================================================================
	/* * *  6. Скрытое создание плагина  * * */

	/**
	 * Метод для переопределения у потомков - инициализация необходимых полей при создании плагина
	 * Переопределять конструктор неудобно (надо вызывать в нем родительский, помнить какие надо параметры принять и пробросить родительскому...)
	 * Этот метод избавляет от необходимости переопределять конструктор
	 * */
	protected function init() {
		// pass
	}

	/**
	 *
	 * */
	protected function beforeAddParams($params) {
		return $params;
	}

	/**
	 *
	 * */
	protected function afterAddParams($params) {
	}


	//=========================================================================================================================
	/* * *  7. Информация необходимая для билдера  * * */

	/**
	 * Сборка информации непосредственно о самом плагине
	 * @return array : $info = [
	 *	'name'
	 *	'main'
	 *	'params'
	 *	'images'
	 *	'screenModes'
	 * ]
	 * */
	public function getSelfInfo() {
		$config = $this->config;
		$info = [
			'name' => $this->_name,
			'anchor' => $this->anchor,
		];

		// Набор произвольных данных
		$params = $this->clientParams->getProperties();
		if (!empty($params)) {
			$info['params'] = $params;
		}

		// Источник изображений плагина
		if (isset($config['images'])) {
			$info['images'] = $this->conductor->getImagePathInSite();
		}

		// Режимы отображения
		if (!empty($this->screenModes)) {
			$info['screenModes'] = $this->screenModes;
		}

		$widgetBasicCssList = $this->widgetBasicCssList();
		if (!empty($widgetBasicCssList)) {
			$list = [];
			foreach ($widgetBasicCssList as $key => $value) {
				$list[str_replace('\\', '.', $key)] = $value;
			}

			$info['wgdl'] = $list;
		}

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
