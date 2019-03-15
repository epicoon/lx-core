<?php

namespace lx;

/**
 * @group {i18n:widgets}
 * */
class ImageSlider extends BoxSlider {
	public function __construct($config=[]) {
		$config = DataObject::create($config);
		$config->count = 0;
		parent::__construct($config);

		$this->setImages( $config->getFirstDefined('images', []) );
	}

	public function setImages($images) {
		if ($this->slides()->len != count($images))
			$this->setSlides(count($images));

		$this->slides()->each(function($a, $i) use ($images) {
			$a->picture($images[$i]);
		});
		return $this;
	}
}
