<?php

namespace lx;

class ModelFieldRelation {
	protected $schema;
	protected $name;
	protected $relativeModelName;
	protected $isArray;

	public function __construct($schema, $name, $data) {
		$this->schema = $schema;
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

	public function getSchema() {
		return $this->schema;
	}

	public function getRelativeSchema() {
		if (preg_match('/\./', $this->relativeModelName)) {
			return \lx::getModelManager($this->relativeModelName)->getSchema();
		}

		return $this->getProvider()->getSchema($this->relativeModelName);
	}

	public function getProvider() {
		return $this->schema->getProvider();
	}
}
