<?php

namespace lx;

class TextBox extends Rect {
	protected function preBuild($config='') {
		if (is_string($config)) $config = ['text' => $config];
		$config = DataObject::create($config);

		if (!$config->key) $config->key = 'text';
		if ($config->text) $config->html = $config->extract('text');

		$config->width = 'auto';
		$config->height = 'auto';

		return parent::preBuild($config);
	}
	
	public function value($val=null) {
		if ($val === null) return $this->html();
		$this->html($val);
	}

	public function setFontSize($sz) {
		$this->style('font-size', $sz);
	}

	public function ellipsis() {
		//todo можно как стиль оформить
		$this->style([
			'overflow' => 'hidden',
			'white-space' => 'nowrap',
			'text-overflow' => 'ellipsis'
		]);
		if ($this->width() == 'auto') {
			$this->width('100%');
		}
	}

	public function adapt() {
		$this->onpostunpack('.adapt');	
	}
}
