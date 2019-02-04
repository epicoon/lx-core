<?php

namespace lx;

class ModelSchema {
	protected
		$name,
		$tableName,
		$fieldNames = [],
		$fields = [];

	public function __construct($name, $schema) {
		$this->name = $name;
		if (isset($schema['table'])) $this->tableName = $schema['table'];

		$this->fieldNames[] = $this->pkName();
		$this->fields[$this->pkName()] = ModelField::create($this->pkName(), ['type' => ModelField::TYPE_INTEGER_SLUG]);

		$forbidden = isset($schema['forbidden']) ? $schema['forbidden'] : [];

		foreach ($schema['fields'] as $fieldName => $fieldData) {
			$this->fieldNames[] = $fieldName;

			if (!is_array($fieldData)) {
				$fieldData = $this->complementFieldData($fieldData);
			}

			if (array_search($fieldName, $forbidden) !== false) {
				$fieldData['forbidden'] = true;
			}

			$this->fields[$fieldName] = ModelField::create($fieldName, $fieldData);
		}
	}

	public function getName() {
		return $this->name;
	}

	public function getTableName() {
		return $this->tableName;
	}

	public function pkName() {
		//todo как-то это тоже можно инициализировать, н-р не в филдах, а  table: ... \n  pk: integer id
		return 'id';
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
