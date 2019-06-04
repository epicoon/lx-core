<?php

namespace lx;

/**
 * @group {i18n:widgets}
 * */
class Image extends Rect {
	public function __construct($config = []) {
		$config = DataObject::create($config);
		parent::__construct($config);

		$src = null;
		if ($config->filename) $src = $this->imagePath($config->filename);
		else if ($config->src) $src = $config->src;

		if ($src) $this->picture($src);
		return $this;
	}

	/**
	 * Тэг класса
	 * */
	protected function tagForDOM() {
		return 'img';
	}

	public function picture($url = null) {
		$this->attrs['src'] = $url;
	}

	public function adapt() {
		$this->onpostunpack('.adapt');
	}
}
