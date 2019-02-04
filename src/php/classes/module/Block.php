<?php

namespace lx;

class Block extends Box {
	public
		$renderIndex = null,
		$renderParams = [],
		$clientParams = [];

	protected
		$file,  // файл с исходным кодом
		$relativeRenderPath = '',  // можно добавить путь, в котором будут искаться подключаемые блоки

		$self = [],  // свойства виджета самого блока (стили, атрибуты, поля) - собирают инфу из $widget при рендеринге
		$htmlContent = '',  // верстка содержимого
		$lx = [],    // пояснительная записка к содержимому

		$blocks = [],  // вложенные блоки
		$js = null,
		$bootstrap = null;

	public function __construct($path) {
		$this->parent = false;

		$this->_children = new Vector();
		$this->classList = new Vector();
		$this->positioningStrategy = new PositioningStrategy($this);

		$this->file = new File($path);
	}

	/**
	 * Для использования во вьюхах
	 * */
	public static function render($blockConfig, $boxConfig = []) {
		if (!array_key_exists('key', $boxConfig)) {
			$key = null;
			if (is_string($blockConfig)) {
				$key = $blockConfig;
			} elseif (is_array($blockConfig)) {
				if (array_key_exists('path', $blockConfig)) {
					$key = $blockConfig['path'];
				}
			}
			if ($key) {
				$key = str_replace('/', '_', $key);
				$boxConfig['key'] = $key;
			}
		}
		$box = new Box($boxConfig);
		$box->setBlock($blockConfig);
		return $box;
	}

	/**
	 *
	 * */
	public function setRenderPath($path) {
		if ($path && !preg_match('/\/$/', $path)) $path .= '/';
		$this->relativeRenderPath = $path;
	}

	/**
	 *
	 * */
	public function useRenderPath($path) {
		if ($path{0} == '/' || $path{0} == '@') return $path;
		return $this->relativeRenderPath . $path;
	}

	/**
	 *
	 * */
	public function setJs($code) {
		$this->js = $code;
	}

	/**
	 *
	 * */
	public function setBootstrap($code) {
		$this->bootstrap = $code;
	}

	/**
	 * Для использования в коде построения блока - добавить вложенный блок
	 * */
	public function addBlock($name, $config = [], $renderParams = [], $clientParams = []) {
		if (!isset($config['key'])) {
			// слэши заменяются, т.к. в имени задается путь и может их содержать, а ключ должен быль одним словом 
			$config['key'] = str_replace('/', '_', $name);
		}
		$class = (isset($config['widget']))
			? $config['widget']
			: Box::class;
		$block = new $class($config);

		$block->setBlock([
			'path' => $this->useRenderPath($name),
			'renderParams' => $renderParams,
			'clientParams' => $clientParams
		]);
		return $block;
	}

	/**
	 *
	 * */
	public function addBlocks($list) {
		$result = [];
		foreach ($list as $key => $blockConfig) {
			$config = [];
			$renderParams = [];
			$clientParams = [];

			if (is_numeric($key)) {
				if (is_array($blockConfig)) {
					if (!array_key_exists('path', $blockConfig)) continue;
					$path = $blockConfig['path'];
					if (isset($blockConfig['config'])) $config = $blockConfig['config'];
					if (isset($blockConfig['renderParams'])) $config = $blockConfig['renderParams'];
					if (isset($blockConfig['clientParams'])) $renderParams = $blockConfig['clientParams'];
				} elseif (is_string($blockConfig)) {
					$path = $blockConfig;
				} else continue;
			} elseif (is_string($key)) {
				$path = $key;
				if (is_array($blockConfig)) {
					if (isset($blockConfig['renderParams'])) {
						$renderParams = $blockConfig['renderParams'];
						unset($blockConfig['renderParams']);
					}
					if (isset($blockConfig['clientParams'])) {
						$clientParams = $blockConfig['clientParams'];
						unset($blockConfig['clientParams']);
					}
					if (isset($blockConfig['config'])) $config = $blockConfig['config'];
					else $config = $blockConfig;
				}
			}

			$result[] = $this->addBlock($path, $config, $renderParams, $clientParams);
		}
		return $result;
	}

	/**
	 * Для использования в коде построения блока - добавить вложенный блок
	 * Формат $block = [
	 *	`blockName` => `blockConfig | blockHeight`,
	 *	...
	 * ]
	 * */
	public function addBlockStream($blocks, $config = []) {
		$streamBox = new Box($config);
		if (!isset($config['stream']))
			$streamBox->stream();

		$streamBox->begin();

		foreach ($blocks as $name => $blockConfig) {
			if (is_numeric($name)) {
				$name = $blockConfig;
				$blockConfig = [];
			} else if (is_numeric($blockConfig) || is_string($blockConfig)) {

				$blockConfig = ['height' => $blockConfig];

			}
			$this->addBlock($name, $blockConfig);
		}

		$streamBox->end();
	}

	/**
	 * Можно подключить много попапов
	 * */
	public function addPopups($list) {
		/*
		//todo
		- сейчас только активбоксом может быть попап - наверное надо варианты поддерживать
		- не поддерживается подгрузка попапов с $renderParams, $clientParams
		*/
		foreach ($list as $key => $config) {
			$config['key'] = $key;
			$popup = new ActiveBox($config);
			$popup->get('body')->setBlock([
				'path' => $this->useRenderPath($key)
			]);
			$popup->hide();
		}
	}

	/**
	 * Путь к файлу с кодом блока
	 * */
	public function getPath() {
		return $this->file->getPath();
	}

	/**
	 * Родительская директория блока
	 * */
	public function parentDirectory() {
		return $this->file->getParentDir();
	}

	/**
	 * Вложенные блоки
	 * */
	public function getBlocks() {
		return $this->blocks;
	}

	/**
	 * Пояснительная записка - массив lx-информации для дочерних элементов
	 * */
	public function getlx() {
		return $this->lx;
	}

	/**
	 * Эмуляция рендеринга для уже существующего элемента, в который грузится данный блок
	 * Упаковка инфы по самому блоку
	 * */
	public function runRender() {
		$this->beforeRender();
		$this->whileRender($this);

		if (!empty($this->attrs))
			$this->self['attrs'] = $this->attrs;
		if (!empty($this->style))
			$this->self['style'] = $this->style;
		if (!empty($this->_prop))
			$this->self['props'] = $this->_prop;
	}

	/**
	 * Метод, используемый виджетами при рендеринге - открытие тэга
	 * */
	public function renderWidgetBegin($widget, $config = []) {
		$blockInfo = $widget->extract('blockInfo');
		if ($blockInfo) {
			$block = $this->addInnerBlock($blockInfo);
			if ($block !== null) {
				$widget->ib = $block->renderIndex;
				if (!empty($block->clientParams)) {
					$widget->ibp = $block->clientParams;
				}
			}
		}

		// В атрибут записываем индекс сопроводительной информации для тэга
		// +1 т.к. нулевое значение не попадает в атрибуты. На стороне клиента единица вычитается.
		$config['attrs']['lx'] = count($this->lx) + 1;
		$this->lx[] = $widget->getLx();

		$attrs = [];
		foreach ($config['attrs'] as $key => $value) {
			if ($value != '') $attrs[] = "$key='$value'";
		}
		$attrs = implode(' ', $attrs);
		$style = '';
		$styleContent = '';
		foreach ($config['style'] as $key => $value) $styleContent .= "$key:$value;";
		if ($styleContent != "") $style = "style='$styleContent'";
		$html = isset($config['html']) ? $config['html'] : '';

		$tag = $widget->tag;
		$this->addHTML( "<$tag $attrs $style>$html" );
	}

	/**
	 * Метод, используемый виджетами при рендеринге - закрытие тэга
	 * */
	public function renderWidgetEnd($tag='div') {
		$this->addHTML( "</$tag>" );
	}

	/**
	 * Здесь собирается код html в процессе рендеринга элементов
	 * */
	private function addHTML($str) {
		$this->htmlContent .= $str;
	}

	/**
	 * Добавление вложенного блока
	 * */
	private function addInnerBlock($blockInfo) {
		$path = $blockInfo[0];
		$renderParams = $blockInfo[1];
		$clientParams = $blockInfo[2];

		$fullPath = $this->getFullPath($path);
		if (!file_exists($fullPath)) $fullPath .= '.php';
		if (!file_exists($fullPath)) {
			//todo сообщение о том, что блок не найден
			return null;
		}

		$block = Renderer::active()->registerBlock($fullPath, $renderParams, $clientParams);
		if ($block === null) return null;

		$this->blocks[$block->renderIndex] = $block;
		return $block;
	}

	/**
	 * Определить путь к подключаемому блоку
	 * */
	private function getFullPath($path) {
		//todo - алиасы модуля, сервиса?
		return \lx::$conductor->getFullPath($path, $this->parentDirectory()->getPath());
	}

	/**
	 * Получить консолидированный результат рендеринга
	 * */
	public function getData() {
		$hasContent = function($field) {
			return !($field === [] || $field === '' || $field === null);
		};
		$result = [];

		if ($hasContent($this->self)) $result['self'] = $this->self;
		if ($hasContent($this->htmlContent)) $result['html'] = $this->htmlContent;
		if ($hasContent($this->lx)) $result['lx'] = $this->lx;
		if ($hasContent($this->js)) $result['js'] = $this->js;
		if ($hasContent($this->bootstrap)) $result['bs'] = $this->bootstrap;
		return $result;
	}
}
