<?php

namespace lx;

class User extends \lx\Model {
	public function __construct($params = []) {
		parent::__construct();
	}

	public function isGuest() {
		return $this->data === null;
	}
}
