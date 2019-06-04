<?php

namespace lx;

/**
 * @group {i18n:widgets}
 * */
class Paginator extends Box {
	public function __construct($config=[]) {
		$config = DataObject::create($config);
		parent::__construct($config);
		$this->addToPostBuild($config);
	}
}
