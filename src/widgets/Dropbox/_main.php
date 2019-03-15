<?php

namespace lx;

/**
 * @group {i18n:widgets}
 * */
class Dropbox extends Box {
	public function __construct($config=[]) {
		$config = DataObject::create($config);
		parent::__construct($config);

		$this->style('overflow', 'visible');

		if ($config->button !== false) {
			$but = new Rect([
				'parent' => $this,
				'key' => 'but',
				'css' => 'lx-Dropbox-but'
			]);
		}

		$this->data = $config->options ? $config->options : [];
		$this->value($config->value);
	}

	public function selectedText() {
		if ($this->val === null) return '';
		return $this->data[$this->val];
	}

	public function value($val=-1) {
		if ($val === -1) return $this->val;

		$this->val = $val;
		$this->text($this->selectedText());
		return $this;
	}

	public function options($content=null) {
		if ($content===null) return $this->data;

		$this->data = $content;
		return $this;
	}

	public function addOption($text) {
		//todo .isAssoc - ключи чтобы были не только числовые
		$this->data[] = $text;

		return $this;
	}
}

//todo - верстку таблицы-синглтона сделать тут, сейчас она всегда генерится клиентом
