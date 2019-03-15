<?php

namespace lx;

/**
 * @group {i18n:widgets}
 * */
class BoxSlider extends Box {
	public function __construct($config=[]) {
		$config = DataObject::create($config);
		parent::__construct($config);

		// Настройки для таймера
		$timer = [];
		if ($config->type)          $timer['type'] = $config->type;
		if ($config->showTime)      $timer['showTime'] = $config->showTime;
		if ($config->slideTime)     $timer['slideTime'] = $config->slideTime;
		if ($config->auto !== null) $timer['auto'] = $config->auto;
		if (!empty($timer)) $this->__timer = $timer;

		$this->setSlides($config->getFirstDefined('count', 1));

		$this->style('overflow-x', 'hidden');
	}

	public function slide($num) {
		return $this->get('s')->at($num);
	}

	public function slides() {
		return $this->get('s');
	}

	protected function setSlides($count) {
		if (!$count) return;

		$slides = Box::construct($count, [
			'parent' => $this,
			'key' => 's',
			'size' => [100, 100]
		]);

		$slides->call('hide');
		$slides->at(0)->show();
		if ($count > 1) $this->initButtons();
	}

	protected function initButtons() {
		$this->begin();
		new Rect([
			'key' => 'pre',
			'geom' => ['0%', '40%', '5%', '20%'],
			'css' => 'lx-IS-button',
			'style' => ['rotate' => 180]
		]);
		new Rect([
			'key' => 'post',
			'geom' => ['95%', '40%', '5%', '20%'],
			'css' => 'lx-IS-button'
		]);
		$this->end();
	}
}
