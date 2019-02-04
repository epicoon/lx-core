<?php

namespace lx;

class SlotPositioningStrategy extends PositioningStrategy {
	protected
		$k,
		$cols,
		$aling = null;

	public function __construct($owner, $config=[]) {
		parent::__construct($owner, $config);
		$config = DataObject::create($config);

		$this->defaultFormat = self::FORMAT_PX;
		$this->innerFormat = self::FORMAT_PX;

		if ($config->hasProperties()) $this->init($config);
	}

	public function init($config) {
		$config = DataObject::create($config);

		$this->k = $config->k ? $config->k : 1;
		$this->cols = $config->cols ? $config->cols : 1;
		if ($config->align) $this->align = $config->align;

		$this->setIndents($config);

		$count = 0;
		if ($config->count) {
			$count = $config->count;
		} else if ($config->rows) {
			$count = $this->cols * $config->rows;
		} else return;

		$type = $config->type ? $config->type : Box::class;
		$type::construct($count, [
			'key' => 's',
			'parent' => $this->owner
		]);
	}

	public function pack() {
		$str = parent::pack();
		$str .= ";k:{$this->k};c:{$this->cols}";
		if ($this->align) $str .= ";a:{$this->align}";
		return $str;		
	}

	public function tryReposition($elem, $param, $val) {
		return false;
	}

	public function actualize() {
		//todo можно пробовать расчитать все тут
		$this->needJsActualize = true;
	}
}
