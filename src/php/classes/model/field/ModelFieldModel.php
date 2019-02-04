<?php

namespace lx;

class ModelFieldModel extends ModelField {
	protected $modelName;

	protected function __construct($name, $data) {
		parent::__construct($name, $data);

		$this->modelName = $data['model'];

		//todo notNull???
	}

	public function getModelName() {
		return $this->modelName;
	}

	public function forbidden() {
		return true;
	}
}
