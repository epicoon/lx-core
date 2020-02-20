<?php

namespace lx;

class Cookie extends DataObject
{
	public function __set($prop, $val) {
		if ($val === null) {
			$this->drop($prop);
		} else {
			setcookie($prop, $val);
			parent::__set($prop, $val);
		}
	}

	public function set($name, $val, $expire)
	{
		if ($val === null) {
			$this->drop($name);
		} else {
			setcookie($name, $val, $expire);
			parent::__set($name, $val);
		}
	}

	public function drop($name) {
		$this->extract($name);
		setcookie($name, '', time() - 1);
	}
}
