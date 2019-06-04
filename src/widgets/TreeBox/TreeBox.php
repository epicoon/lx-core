<?php

namespace lx;

/**
 * @group {i18n:widgets}
 * */
class TreeBox extends Box {
	/* config = {
		// стандартные для lx.Widget,
		
		data: lx.Tree
		indent: 10,
		step: 5,
		leafHeight: 18,
		labelWidth: 250,
		add: false,
		leaf: function
	} */

	const
		FORBIDDEN_ADDING = 0,
		ALLOWED_ADDING = 1,
		ALLOWED_ADDING_BY_TEXT = 2;

	public function __construct($config=[]) {
		$config = DataObject::create($config);
		parent::__construct($config);

		$this->overflow('auto');

		//todo см. тудуху в TreeBox.js
		$this->indent     = $config->indent ? $config->indent : 10;
		$this->step       = $config->step ? $config->step : 5;
		$this->leafHeight = $config->leafHeight ? $config->leafHeight : 18;
		$this->labelWidth = $config->labelWidth ? $config->labelWidth : 250;
		$this->addMode    = $config->add ? $config->add : self::FORBIDDEN_ADDING;
		$this->rootAdding = $config->rootAdding !== null
			? $config->rootAdding
			: (bool)$this->addMode;

		$this->addToPostBuild($config, ['data', 'leaf']);

		$w = $this->step * 2 + $this->leafHeight + $this->labelWidth;
		$work = new Box([
			'parent' => $this,
			'key' => 'work',
			'width' => $w . 'px',
		]);
		$work->overflow('visible');

		new Box([
			'parent' => $this,
			'key' => 'move',
			'left' => $w . 'px',
			'width' => $this->step . 'px',
			'style' => ['cursor'=>'ew-resize']
		]);
	}

	public function beforeRender() {
		if (isset($this->__postBuild['data']) && $this->__postBuild['data'] instanceof Tree)
			$this->__postBuild['data'] = $this->__postBuild['data']->toJSON();

		parent::beforeRender();
	}
}
//todo надо ли генерить здесь листы? Вроде виджет не самый распространенный, пусть js его отображением занимается
