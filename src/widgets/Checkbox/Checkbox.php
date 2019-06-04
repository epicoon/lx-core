<?php

namespace lx;

/**
 * @group {i18n:widgets}
 * */
class Checkbox extends Rect {
	public function __construct($config=[]) {
		$config = DataObject::create($config);
		parent::__construct($config);

		$this->value((bool)$config->value);
	}

	public function getBaseCss() {
		return ($this->disabled())
			? parent::getDisabledClass() . '-' . ((int)$this->value())
			: parent::getEnabledClass() . '-' . ((int)$this->value());
	}

	public function value($val = -1) {
		if ((int)$val == -1) return $this->state;

		$this->removeClass( $this->getBaseCss() );
		$this->state = $val;
		$this->addClass( $this->getBaseCss() );

		return $this;
	}
}
