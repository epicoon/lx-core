<?php

namespace lx;

/**
 * @group {i18n:widgets}
 * */
class ActiveBox extends Box {
	public static
		$DEFAULT_MOVE = true,
		$DEFAULT_RESIZE = true,
		$DEFAULT_ADHESIZE = false,
		$HEADER_HEIGHT = '25px',
		$RESIZER_SIZE = '12px';


	public function __construct($config=[]) {
		$config = DataObject::create($config);
		parent::__construct($config);

		$this->setHeader($config);
		if ($config->body !== false) $this->setBody($config->body ? $config->body : Box::class);

		if ($config->resize === null) $config->resize = self::$DEFAULT_RESIZE;
		if ($config->resize !== false) $this->setResizer($config);

		if ($config->adhesive === null) $config->adhesive = self::$DEFAULT_ADHESIZE;
		if ($config->adhesive !== false) $this->adhesive = true;
	}
	
	protected function setHeader($config) {
		if (!$config->header && !$config->headerHeight && !$config->headerConfig) return;

		$headerConfig = $config->headerConfig ? $config->headerConfig : [];
		if ($config->headerHeight) $headerConfig['height'] = $config->headerHeight;
		if (!isset($headerConfig['height'])) $headerConfig['height'] = self::$HEADER_HEIGHT;
		if ($config->header) $headerConfig['text'] = $config->header;
		$headerConfig['parent'] = $this;
		$headerConfig['key'] = 'header';

		$header = new Box($headerConfig);
		$header->align(\lx::CENTER, \lx::MIDDLE, 'text');
		$header->fill('lightgray');

		if ($config->move !== false && self::$DEFAULT_MOVE)
			$header->move(['parentMove' => true]);
	}

	protected function setResizer($config) {
		$el = new Rect([
			'parent' => $this,
			'key' => 'resizer',
			'geom' => [null, null, self::$RESIZER_SIZE, self::$RESIZER_SIZE, 0, 0]
		]);
		$el->fill('gray');
		$el->move(['parentResize' => true]);

		if ($config->adhesive) {
			$el->moveParams['xMinMove'] = 5;
			$el->moveParams['yMinMove'] = 5;
		}
	}

	protected function setBody($constructor) {
		$config = [];
		if (is_array($constructor)) {
			$config = $constructor[1];
			$constructor = $constructor[0];
		}
		$config['parent'] = $this;
		$config['key'] = 'body';
		new $constructor($config);
	}

	protected function modifyNewChildConfig($config) {
		if ($config->key == 'resizer') return $config;
		if (isset($this->children['header'])) {
			$config->top = $this->children['header']->height();
			$config->nextSibling = $this->children['header'];
		}
		if (isset($this->children['resizer']))
			$config->nextSibling = $this->children['resizer'];
		return $config;
	}
}
