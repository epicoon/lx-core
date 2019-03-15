<?php

namespace lx;

/**
 * @group {i18n:widgets}
 * */
class Slider extends Box {
	public function __construct($config=[]) {
		$config = DataObject::create($config);
		parent::__construct($config);

		$this->min  = $config->getFirstDefined('min', 0);
		$this->max  = $config->getFirstDefined('max', 100);
		$this->step = $config->getFirstDefined('step', 1);

		$value = $config->getFirstDefined('value', 0);
		if ($value < $this->min) $value = $this->min;
		if ($value > $this->max) $value = $this->max;
		$this->_value = $value;

		$track = new Rect([
			'parent' => $this,
			'key' => 'track',
			'geom' => ['0%', '17%', '100%', '66%'],
			'css' => 'lx-slider-track'
		]);

		$handle = new Rect([
			'key' => 'handle',
			'parent' => $this,
			'css' => 'lx-slider-handle'
		]);
	}

	public function value($num = null) {
		if ($num === null) return $this->_value;
		$this->_value = $num;
	}

	public function change($func) {
		$this->on('change', $func);
		return $this;
	}
}
