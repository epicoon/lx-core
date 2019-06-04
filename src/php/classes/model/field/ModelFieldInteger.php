<?php

namespace lx;

class ModelFieldInteger extends ModelField {
	const NOT_NULL_DEFAULT = 111;

	protected function init($data) {
		//todo min max
	}

	public function suitableType($value) {
		return (filter_var($value, FILTER_VALIDATE_INT) !== false);
	}

	public function normalizeValue($value) {
		if ( ! $this->suitableType($value)) {
			throw new \Exception("ModelFieldInteger typecast error by value '$value'", 400);
		}
		return (int)$value;
	}
}
