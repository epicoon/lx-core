<?php

namespace lx;

/**
 *
 * */
class Renderer {
	private static
		$instanceStack = [],
		$keyCounter = 0;

	private
		$module,

		$blocks,  // линейный массив с зарегистрированными блоками
		$blocksByKey,
		$currentBlock,

		$autoParentStack;

	public function __construct() {
		$this->module = null;

		$this->blocks = [];
		$this->blocksByKey = [];
		$this->currentBlock = null;

		$this->autoParentStack = new Vector();

		self::$instanceStack[] = $this;
	}

	/**
	 * При уничтожении рендерера надо актуализировать стак собираемых модулей
	 * */
	public function endWork() {
		array_pop(self::$instanceStack);
	}

	/**
	 * Активным рендерером считается находящийся на вершине стека
	 * */
	public static function active() {
		return end(self::$instanceStack);
	}

	/**
	 * Модуль, собирающийся в данный момент
	 * */
	public static function activeModule() {
		if (empty(self::$instanceStack)) return null;

		return self::active()->getModule();
	}

	/**
	 * Ключ для очередного рендерящегося элемента
	 * */
	public static function getKey() {
		return 'e' . Math::decChangeNotation(self::$keyCounter++, 62);
	}

	/**
	 * Количество выданных ключей
	 * */
	public static function getKeyCount() {
		return self::$keyCounter;
	}

	public function __set($name, $val) {
		if ($name == 'autoParent') {
			if ($val === null) $this->resetAutoParent();
			else $this->setAutoParent($val);
			return;
		}

		if (property_exists($this, $name)) $this->$name = $val;
	}

	public function __get($name) {
		if ($name == 'autoParent') return $this->getAutoParent();
		if (property_exists($this, $name)) return $this->$name;
		return null;
	}

	/**
	 * Получить модуль, который сейчас собирается
	 * */
	public function getModule() {
		return $this->module;
	}

	/**
	 * Получить блок, который сейчас собирается
	 * */
	public function getCurrentBlock() {
		return $this->currentBlock;
	}

	/**
	 * Установить модуль для сборки
	 * */
	public function setModule($Module) {
		$this->module = $Module;
	}

	/**
	 * Создание блока по его пути
	 * */
	private function createBlock($path, $renderParams=[], $clientParams=[]) {
		$b = new Block($path);
		$b->renderParams = $renderParams;
		$b->clientParams = $clientParams;
		$b->renderIndex = count($this->blocks);
		$this->blocks[] = $b;
		return $b;
	}

	/**
	 * Регистрация блока модуля
	 * */
	public function registerBlock($path, $renderParams=[], $clientParams=[]) {
		/*
		//todo - пока захардкожены ключевые файлы для блока, представленного каталогом
		надо делать выход на какую-то конфигурацию
		*/
		$blockPath = is_dir($path)
			? $this->getBlockMainPhpPath($path)
			: $path;
		if (!file_exists($blockPath)) {
			return null;
		}
		$key = $this->keyByPathAndParams($blockPath, $renderParams);
		if (array_key_exists($key, $this->blocksByKey)) {
			return $this->blocksByKey[$key];
		}

		$b = $this->createBlock($blockPath, $renderParams, $clientParams);
		if (is_dir($path)) {
			$js = $this->getBlockMainJsPath($path);
			if (file_exists($js)) {
				$f = new File($js);
				$code = JsCompiler::compileCode($f->get(), $f->getPath());

				//todo!!!!!!!!!!!!!!!!
				$code = Minimizer::clearSpacesKOSTYL($code);
				$code = preg_replace('/"/', '\"', $code);

				$b->setJs($code);
			}

			$bootstrap = $path . '/_bootstrap.js';
			if (file_exists($bootstrap)) {
				$f = new File($bootstrap);

				$code = JsCompiler::compileCode($f->get(), $f->getPath());

				//todo!!!!!!!!!!!!!!!!
				$code = Minimizer::clearSpacesKOSTYL($code);
				$code = preg_replace('/"/', '\"', $code);

				$b->setBootstrap($code);
			}
		}


		$this->blocksByKey[$key] = $b;
		return $b;
	}

	/**
	 * Ключ для блока формируется с учетом параметров рендеринга
	 * */
	private function keyByPathAndParams($path, $params) {
		if (empty($params)) return $path;

		$paramsArray = [];
		foreach ($params as $key => $value) {
			$paramsArray[] = $key . '_' . $value;
		}

		return $path . '_' . implode('_', $paramsArray);
	}

	/**
	 * Установить на вершину стека дефолтный родительский элемент
	 * */
	public function setAutoParent($el) {
		// if ($el == 'Module') $el = ModuleCompiler::element();  // бредятина древняя
		$this->autoParentStack->push($el);
	}

	/**
	 * Получить дефолтный родительский элемент
	 * */
	public function getAutoParent() {
		return $this->autoParentStack->last();
	}

	/**
	 * Отменить дефолтный родительский элемент, находящийся на вершине стека
	 * */
	public function popAutoParent() {
		return $this->autoParentStack->pop();
	}

	/**
	 * Сбросить весь стек дефолтных родительских элементов и установить переданный
	 * */
	public function resetAutoParent($elem=null) {
		$this->autoParentStack->reset();
		if ($elem !== null) $this->setAutoParent($elem);
	}

	/**
	 * Рекурсивный рендеринг вложенных блоков
	 * */
	private function renderBlockRe($Block) {
		$this->resetAutoParent($Block);
		$Module = $this->module;
		if (!empty($Block->renderParams)) extract($Block->renderParams);
		/* Отсюда передается доступ к контекстным переменным
		 * $Module
		 * $Block
		 * ... и переданным параметрам
		 * */
		// 
		require($Block->getPath());

		// собирается html и пояснительная записка контента
		$Block->runRender();

		// onload уровня блока??? todo

		// рендерить вложенные блоки
		foreach ($Block->getBlocks() as $block)
			$this->renderBlockRe($block);
	}

	/**
	 * Консолидация данных по отрендеренным блокам
	 * */
	private function packBlocks() {
		// собираем данные для отправки
		$arr = [];
		foreach ($this->blocks as $block) $arr[] = $block->getData();
		return $arr;
	}

	/**
	 * Рендерит блок с рекурсией по вложенным и возвращает готовые для отправки данные
	 * */
	public function renderBlock($path) {
		$block = $this->createBlock($path);
		$this->renderBlockRe($block);
		return $this->packBlocks();
	}

	/**
	 *
	 * */
	private function getBlockMainPhpPath($path) {
		if (file_exists($path . '/_main.php')) {
			return $path . '/_main.php';
		}
		$arr = explode('/', $path);
		$name = end($arr);
		if (file_exists($path . '/_' . $name . '.php')) {
			return $path . '/_' . $name . '.php';
		}
		if (file_exists($path . '/' . $name . '.php')) {
			return $path . '/' . $name . '.php';
		}
	}

	/**
	 *
	 * */
	private function getBlockMainJsPath($path) {
		if (file_exists($path . '/_main.js')) {
			return $path . '/_main.js';
		}
		$arr = explode('/', $path);
		$name = end($arr);
		if (file_exists($path . '/_' . $name . '.js')) {
			return $path . '/_' . $name . '.js';
		}
		if (file_exists($path . '/' . $name . '.js')) {
			return $path . '/' . $name . '.js';
		}
	}
}
