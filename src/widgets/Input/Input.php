<?php

namespace lx;

/**
 * @group {i18n:widgets}
 * */
class Input extends Rect {
	public function __construct($config = []) {
		$config = DataObject::create($config);
		parent::__construct($config);

		if ($config->hint) $this->attr('placeholder', $config->hint);

		if ($config->value) $this->setValue($config->value);

		return $this;
	}

	/**
	 * Тэг класса
	 * */
	protected function tagForDOM() {
		return 'input';
	}

	public function setValue($value) {
		$this->attrs['value'] = $value;
	}

	public function focus($func) { $this->on('focus', $func); return $this; }
	public function blur($func) { $this->on('blur', $func); return $this; }
}
