<?php

namespace lx;

class ModuleBuilder {
	const DEFAULT_MODULE_TITLE = 'lx';

	private static
		$instanceStack = [],

		$dynamicModules = [],
		$commonWidgetList = [],
		$scriptMap = [],
		$cssMap = [],
		$widgetsCode = '';

	private $_module;
	private $_uniqKey = null;

	private $_moduleInfo = null;
	private $_scripts = null;
	private $_css = null;
	private $_blocks = null;
	private $_bootstrapJs = null;
	private $_mainJs = null;

	private $widgetList = [];
	private $errors = [];
	private $done = false;

	public function __construct($module) {
		$this->_module = $module;
	}

	/**
	 * Метод, возвращающий данные по модулю для отправки
	 * */
	public function getResult() {
		if (!$this->build()) {
			return false;
		}

		$data = self::getModulesData();
		if (\lx::$dialog->isAjax()) {
			$arr = ['moduleInfo' => $data];

			if (!empty(self::$scriptMap)) {
				$arr['scripts'] = self::$scriptMap;
			}

			if (!empty(self::$cssMap)) {
				$arr['css'] = self::$cssMap;
			}

			if (!empty(self::$commonWidgetList)) {
				$arr['widgets'] = self::prepareWidgetList(self::$commonWidgetList);
			}

			return $arr;
		}

		$scripts = self::getScriptsHtml();
		$css = self::getCssHtml();

		$module = $this->getModule();
		return [
			'title' => $module->title ? $module->title : self::DEFAULT_MODULE_TITLE,
			'moduleInfo' => $data,
			'scripts' => $scripts,
			'css' => $css,
		];
	}

	/**
	 * //todo - сделать по нормальному визуализацию ошибок
	 * */
	public function getError() {
		return end($this->errors);
	}

	/**
	 * Промежуточный шаг, добавляющий информацию о собираемом модуле в общий пул данных по всем модулям, которые нужно собрать в данном запросе
	 * */
	public function build() {
		if ($this->done) return true;

		$module = $this->getModule();
		self::$instanceStack[] = $this;

		$blocks = $this->blocks();
		$bootstrapJs = $this->bootstrapJs();
		$mainJs = $this->mainJs();
		$moduleInfo = $this->moduleInfo();

		$uniqKey = $this->uniqKey();
		$data = "<mi $uniqKey>$moduleInfo</mi $uniqKey>"
			. "<bs $uniqKey>$bootstrapJs</bs $uniqKey>"
			. "<bl $uniqKey>$blocks</bl $uniqKey>"
			. "<mj $uniqKey>$mainJs</mj $uniqKey>";

		self::registerModuleData($uniqKey, $data);
		if (!\lx::$dialog->isAjax()) {
			self::registerWidgetsCode();
		}

		$scripts = $this->scripts();
		if ($scripts) {
			self::registerScripts($uniqKey, $scripts);
		}

		$css = $this->css();
		if ($css) {
			self::registerCss($uniqKey, $css);
		}

		array_pop(self::$instanceStack);

		$this->done = true;
		return true;
	}

	/**
	 * PHP не поддерживает дружественные классы, но есть выход
	 * */
	public function callPrivateModuleMethod($methodName, $args = []) {
		return ClassHelper::call($this->getModule(), $methodName, $args);
	}

	/**
	 * Собираемый модуль
	 * */
	public function getModule() {
		return $this->_module;
	}

	/**
	 * Активным считается находящийся на вершине стека
	 * */
	public static function active() {
		return end(self::$instanceStack);
	}

	/**
	 * Уникальный ключ собираемого модуля
	 * */
	public function uniqKey() {
		if ($this->_uniqKey === null) {
			$this->_uniqKey = self::getUniqKey();
		}

		return $this->_uniqKey;
	}

	/**
	 * //todo
	 * */
	public function isCompiled() {
		return false;
	}

	/**
	 * //todo
	 * */
	public function compiledFilePath() {
		return '';
	}

	/**
	 * Автозагрузик при подключении виджета (в карте классов lx) помечает его как используемый
	 * */
	public static function noteUsedWidget($namespace, $name) {
		// Если нет возможности зарегистрировать
		$active = self::active();

		if ($active) {
			if (!array_key_exists($namespace, $active->widgetList)) {
				$active->widgetList[$namespace] = [];
			}
			$active->widgetList[$namespace][$name] = 1;
		}

		// Если виджет уже зарегистрирован
		if (array_key_exists($namespace, self::$commonWidgetList) 
			&& array_key_exists($name, self::$commonWidgetList[$namespace])
		) {
			return false;
		}

		if (!array_key_exists($namespace, self::$commonWidgetList)) {
			self::$commonWidgetList[$namespace] = [];
		}
		self::$commonWidgetList[$namespace][$name] = 1;

		return true;
	}

	/**
	 * Основные данные по модулю
	 * */
	public function moduleInfo() {
		if ($this->_moduleInfo === null) {
			$info = $this->callPrivateModuleMethod('getSelfInfo');

			// Подсовываем зависимости от виджетов
			if (!empty($this->widgetList)) {
				$info['wgd'] = self::prepareWidgetList($this->widgetList);
			}

			$this->_moduleInfo = json_encode($info);
		}

		return $this->_moduleInfo;
	}

	public function scripts() {
		if ($this->_scripts === null) {
			$this->_scripts = $this->callPrivateModuleMethod('getScripts');
		}

		return $this->_scripts;
	}

	public function css() {
		if ($this->_css === null) {
			$this->_css = $this->callPrivateModuleMethod('getCss');
		}

		return $this->_css;
	}

	public function blocks() {
		if ($this->_blocks === null) {
			$blocks = $this->compileBlock();
			$this->_blocks = json_encode($blocks);
		}

		return $this->_blocks;
	}

	public function bootstrapJs() {
		if ($this->_bootstrapJs === null) {
			$module = $this->getModule();
			$jsBootstrapFile = $module->conductor->getJsBootstrap();
			if (!$jsBootstrapFile) {
				$this->_bootstrapJs = '';
				return '';
			}

			$this->_bootstrapJs = $this->compileJsFile($jsBootstrapFile);
		}

		return $this->_bootstrapJs;
	}

	public function mainJs() {
		if ($this->_mainJs === null) {
			$module = $this->getModule();
			$jsMainFile = $module->conductor->getJsMain();

			$code = '';
			if ($jsMainFile) $code = $jsMainFile->get();
			$pre = $this->prepareJsPre();
			$post = $this->prepareJsPost();
			$code = "$pre$code$post";

			$this->_mainJs = JsCompiler::compileCodeSeeingAjax($code, $jsMainFile ? $jsMainFile->getPath() : ''/*//todo не годится тут такой путь - если нет главного файла*/);
		}

		return $this->_mainJs;
	}

	private function addError($msg) {
		$this->errors[] = $msg;
	}

	private function compileJsFile($file) {
		$code = $file->get();
		if ($code == '') return '';

		return JsCompiler::compileCodeSeeingAjax($code, $file->getPath());
	}

	private static function getUniqKey() {
		$randKey = function() {
			return
				Math::decChangeNotation(Math::rand(0, 255), 16).
				Math::decChangeNotation(Math::rand(0, 255), 16).
				Math::decChangeNotation(Math::rand(0, 255), 16);
		};
		do {
			$uniqRand = $randKey();
		} while ( array_key_exists($uniqRand, self::$dynamicModules) );

		return $uniqRand;;
	}

	private static function registerModuleData($key, $data) {
		self::$dynamicModules[$key] = $data;
	}

	private static function registerWidgetsCode() {
		self::$widgetsCode .= self::getWidgetsCode();
	}

	private static function registerScripts($key, $scripts) {
		self::$scriptMap[$key] = $scripts;
	}

	private static function registerCss($key, $css) {
		self::$cssMap[$key] = $css;
	}



	//=========================================================================================================================
	/* * *  . Непосредственно методы сборки  * * */

	/**
	 * //todo - вроде только корневой блок используется, надо посмотреть нужно ли вообще выбранный блок рендерить, и ели нужно - протестить
	 * Без явного указания имени блока компилится корневой
	 * */
	private function compileBlock($block = null) {
		$module = $this->getModule();
		$blockPath = $module->conductor->getBlockPath($block);
		if ($blockPath === null) return [];

		$renderer = new Renderer();
		$renderer->setModule($module);

		// запуск рекурсии на рендер дерева блоков
		$result = $renderer->renderBlock($blockPath);
		$renderer->endWork();

		return $result;
	}

	private function prepareJsPre() {
		$result = '';
		$preJs = $this->callPrivateModuleMethod('getPreJs');
		foreach ($preJs as $js) {
			if (preg_match('/^\(\)=>/', $js)) $result .= preg_replace('/^\(\)=>/', '', $js);
			else {
				$file = $this->conductor->getJsFile($js);
				if (!$file) continue;
				$result .= $file->get();
			}
		}
		return $result;
	}

	private function prepareJsPost() {
		$result = '';
		$postJs = $this->callPrivateModuleMethod('getPostJs');
		foreach ($postJs as $js) {
			if (preg_match('/^\(\)=>/', $js)) $result .= preg_replace('/^\(\)=>/', '', $js);
			else {
				$file = $this->conductor->getJsFile($js);
				if (!$file) continue;
				$result .= $file->get();
			}
		}
		return $result;
	}


	//=========================================================================================================================
	/* * *  . Методы консолидации результатов сборки  * * */

	/**
	 * Собирает в строку итог полного рендеринга модуля (и всех вложенных модулей)
	 * */
	private static function getModulesData() {
		$result = '';

		// Консолидируем инфу по всем собранным в этом запросе модулям
		$map = self::$dynamicModules;
		foreach ($map as $key => $value) {
			$result .= "<module $key>$value</module $key>";
		}

		if (self::$widgetsCode != '')
			$result .= '<widgets>' . self::$widgetsCode . '</widgets>';

		return $result;
	}

	/**
	 * Для статической загрузки страницы готовит блок с кодом используемых виджетов
	 * */
	private static function getWidgetsCode() {
		$code = WidgetHelper::getWidgetsCode(self::$commonWidgetList);
		$code = JsCompiler::withoutAjaxModification($code);
		return $code;
	}

	/**
	 * Для статической загрузки страницы готовит HTML с подключением внешних js-скриптов
	 * */
	private static function getScriptsHtml() {
		if (empty(self::$scriptMap)) return '';

		$headScripts = '';
		foreach (self::$scriptMap as $key => $scripts) {
			if (isset($scripts['head'])) {
				foreach ($scripts['head'] as $path) {
					$headScripts .= "<script src=\"$path\" name=\"$key\"></script>";
				}
			}
		}

		return [
			'headScripts' => $headScripts
		];
	}

	/**
	 * Для статической загрузки страницы готовит HTML с подключением css-файлов
	 * */
	private static function getCssHtml() {
		if (empty(self::$cssMap)) return '';

		$result = '';
		foreach (self::$cssMap as $key => $css) {
			foreach ($css as $path) {
				$result .= "<link href=\"$path\" name=\"$key\" type=\"text/css\" rel=\"stylesheet\">";
			}
		}

		return $result;
	}

	/**
	 * Вспомогательный метод, переводящий данные о подключенных виджетов в формат, отправляемый клиенту
	 * */
	private static function prepareWidgetList($list) {
		$result = [];
		foreach ($list as $namespace => $names) {
			$result[$namespace] = [];
			foreach ($names as $name => $emptyData) {
				$result[$namespace][] = $name;
			}
		}
		return $result;
	}
}
