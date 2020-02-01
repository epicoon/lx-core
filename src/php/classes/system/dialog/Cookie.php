<?php

namespace lx;

class Cookie extends DataObject
{
	public function __set($prop, $val) {
		setcookie($prop, $val);
		parent::__set($prop, $val);
	}

	public function drop($name) {
		$this->extract($name);
		setcookie($name, '', time() - 1);
	}
}
