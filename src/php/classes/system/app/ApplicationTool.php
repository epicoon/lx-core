<?php

namespace lx;

/**
 * Class ApplicationTool
 * @package lx
 * 
 * @property $app Application
 */
class ApplicationTool {
	/** @var Application */
	private $_app;

	/**
	 * ApplicationTool constructor.
	 * @param $app Application
	 */
	public function __construct($app) {
		$this->_app = $app;
	}

	/**
	 * @param $name
	 * @return Application|null
	 */
	public function __get($name) {
		if ($name == 'app') {
			return $this->_app;
		}

		return null;
	}
}
