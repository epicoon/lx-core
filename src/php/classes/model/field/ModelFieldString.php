<?php

namespace lx;

class ModelFieldString extends ModelFieldSimple {
	const NOT_NULL_DEFAULT = 'DDD';

	protected $_len = 255;

	protected function init($data) {
		if (array_key_exists('len', $data))
			$this->_len = $data['len'];
	}

	public function len() {
		return $this->_len;
	}

	public function suitableType($value) {
		return (is_numeric($value) || is_string($value));
	}

	public function normalizeValue($value) {
		if (is_numeric($value)) $value = ''.$value;
		if (!is_string($value)) {
			throw new \Exception("ModelFieldString typecast error by value '$value'", 400);					
		}
		return $value;
	}

	public function toStringForClient() {
		$arr = [];

		$default = $this->getDefault();
		if ($default !== null) $arr['default'] = "'$default'";

		return $this->compileClientString($arr);
	}
}
