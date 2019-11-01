<?php

namespace lx;

class User extends \lx\Model {
	protected $authFieldName;

	public function __construct($params = []) {
		parent::__construct();
	}

	public function isGuest() {
		return $this->data === null;
	}

	public function setAuthFieldName($name) {
		$this->authFieldName = $name;
	}

	public function getAuthFieldName() {
		if ($this->isGuest()) {
			return '';
		}

		return $this->authFieldName;
	}
}
