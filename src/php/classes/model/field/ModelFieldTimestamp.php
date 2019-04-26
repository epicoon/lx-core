<?php

namespace lx;

class ModelFieldTimestamp extends ModelField {
	const NOT_NULL_DEFAULT = '1970-01-01 00:00';

	protected function init($data) {

	}

	public function getTypeDb() {
		return 'timestamp';
	}

	public function suitableType($value) {
		try {
			$date = new \DateTime($value);

			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

	public function normalizeValue($value) {
		// if (is_numeric($value)) $value = ''.$value;
		// if (!is_string($value)) {
		// 	throw new \Exception("ModelFieldString typecast error by value '$value'", 400);					
		// }
		return $value;
	}

	public function toStringForClient() {
		$arr = [];

		$default = $this->getDefault();
		if ($default !== null) $arr['default'] = "'$default'";

		return $this->compileClientString($arr);
	}
}
