<?php

namespace lx;

class ModelFieldString extends ModelField {
	const NOT_NULL_DEFAULT = '';

	protected $_size = 255;

	protected function init($data) {
		if (array_key_exists('size', $data))
			$this->_size = $data['size'];
	}

	public function typeCompare($field) {
        $result = [];
        if ($this->getType() != $field->getType()) {
            return $result;
        }

        if ($this->size() != $field->size()) {
            $result[] = [
                'property' => 'size',
                'current' => $this->size(),
                'compared' => $field->size()
            ];
        }

        return $result;
    }

	public function getTypeDb() {
		return 'varchar';
	}

	public function getDefinition($params = null) {
		if ($params === null) {
			$params = ['type', 'dbType', 'default', 'notNull', 'size'];
		}

		$result = parent::getDefinition($params);
		if (array_search('size', $params) !== false) {
			$result['size'] = $this->size();
		}
		return $result;
	}

	public function size() {
		return $this->_size;
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
