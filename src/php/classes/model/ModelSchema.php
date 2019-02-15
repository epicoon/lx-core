<?php

namespace lx;

class ModelSchema {
	protected
		$name,
		$pkName = null,
		$tableName,
		$fieldNames = [],
		$fields = [];

	public function __construct($name, $schema) {
		$this->name = $name;
		if (isset($schema['table'])) $this->tableName = $schema['table'];

		$forbidden = isset($schema['forbidden']) ? $schema['forbidden'] : [];

		foreach ($schema['fields'] as $fieldName => $fieldData) {
			$this->fieldNames[] = $fieldName;

			if (!is_array($fieldData)) {
				$fieldData = $this->complementFieldData($fieldData);
			}

			if (array_search($fieldName, $forbidden) !== false) {
				$fieldData['forbidden'] = true;
			}

			if (array_key_exists('pk', $fieldData)) {
				if ($this->pkName === null) {
					$this->pkName = $fieldName;
				} else {
					unset($fieldData['pk']);
				}
			}

			$this->fields[$fieldName] = ModelField::create($fieldName, $fieldData);
		}

		if ($this->pkName === null) {
			$this->pkName = 'id';
			$this->fieldNames[] = $this->pkName;
			$this->fields[$this->pkName] = ModelField::create($this->pkName, ['pk' => true, 'type' => ModelField::TYPE_INTEGER_SLUG]);
		}
	}

	public function getDefinitions($params = null) {
		$result = [];
		foreach ($this->fields as $name => $field) {
			$result[$name] = $field->getDefinition($params);
		}
		return $result;
	}

	public function getName() {
		return $this->name;
	}

	public function getTableName() {
		return $this->tableName;
	}

	public function pkName() {
		return $this->pkName;
	}

	public function fieldNames() {
		return $this->fieldNames;
	}

	public function field($name) {
		return $this->fields[$name];
	}

	public function pkField() {
		return $this->fields[$this->pkName()];
	}

	private function complementFieldData($data) {
		$result = [];
		if ($data === false || $data === true) {
			$result['type'] = 'boolean';
			$result['default'] = $data;
		} elseif (is_numeric($data)) {
			$result['type'] = 'integer';
			$result['default'] = $data;
		} elseif (is_string($data)) {
			$result['type'] = 'string';
			$result['default'] = $data;
		}
		return $result;
	}
}
