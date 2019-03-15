<?php

namespace lx;

/**
 * @group {i18n:widgets}
 * */
class Textarea extends Input {
	/**
	 * Тэг класса
	 * */
	protected function tagForDOM() {
		return 'textarea';
	}

	public function setValue($value) {
		$this->html = $value;
	}
}
