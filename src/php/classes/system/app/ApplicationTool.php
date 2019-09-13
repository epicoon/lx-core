<?php

namespace lx;

/**
 * Class ApplicationTool
 * @package lx
 * 
 * @property $app Application
 */
class ApplicationTool {
	private $_app;
	
	public function __construct($app) {
		$this->_app = $app;
	}

	public function __get($name) {
		if ($name == 'app') {
			return $this->_app;
		}

		return null;
	}
}
