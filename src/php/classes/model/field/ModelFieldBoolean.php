<?php

namespace lx;

class ModelFieldBoolean extends ModelFieldSimple {
	const NOT_NULL_DEFAULT = false;

	protected function init($data) {

	}

	public function suitableType($value) {
		return (filter_var($value, FILTER_VALIDATE_BOOLEAN) !== false);
	}

	public function normalizeValue($value) {
		if (!$this->suitableType($value)) {
			throw new \Exception("ModelFieldBoolean typecast error by value '$value'", 400);
		}
		return (bool)$value;
	}
}
