<?php

namespace lx;

class ApplicationComponent extends ApplicationTool {
	public function __construct($config = []) {
		parent::__construct($config['app']);

		foreach ($config as $key => $value) {
			if (property_exists($this, $key)) {
				$this->$key = $value;
			}
		}
	}	
}
