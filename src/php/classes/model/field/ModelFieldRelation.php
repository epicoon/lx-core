<?php

namespace lx;

class ModelFieldRelation {
	protected $name;
	protected $relativeModelName;
	protected $isArray;

	public function __construct($name, $data) {
		$this->name = $name;

		if (preg_match('/\[\]$/', $data)) {
			$this->isArray = true;
			$this->relativeModelName = trim($data, '][');
		} else {
			$this->isArray = false;
			$this->relativeModelName = $data;
		}
	}

	public function getRelativeModelName() {
		return $this->relativeModelName;
	}

	public function getName() {
	    return $this->name;
    }
}
