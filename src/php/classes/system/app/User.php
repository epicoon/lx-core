<?php

namespace lx;

//TODO убрать эту зависимость - пусть через конфиг инициализируется способ работы с моделью, данные хранятся композицией
use lx\model\Model;

class User extends Model {
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
