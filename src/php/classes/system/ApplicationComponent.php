<?php

namespace lx;

class ApplicationComponent {
	public function __construct($config = []) {
		foreach ($config as $key => $value) {
			if (property_exists($this, $key)) {
				$this->$key = $value;
			}
		}
	}	
}
