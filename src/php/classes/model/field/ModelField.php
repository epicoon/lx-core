<?php

namespace lx;

abstract class ModelField {
	const TYPE_INTEGER_SLUG = 'integer';
	const TYPE_BOOLEAN_SLUG = 'boolean';
	const TYPE_STRING_SLUG = 'string';
	const TYPE_MODEL_SLUG = 'model';
	const TYPE_MODEL_ARRAY_SLUG = 'model-array';

	const TYPE_INTEGER = 1;
	const TYPE_BOOLEAN = 2;
	const TYPE_STRING = 3;
	const TYPE_MODEL = 4;
	const TYPE_MODEL_ARRAY = 5;

	const TYPES = [
		self::TYPE_INTEGER_SLUG => self::TYPE_INTEGER,
		self::TYPE_BOOLEAN_SLUG => self::TYPE_BOOLEAN,
		self::TYPE_STRING_SLUG => self::TYPE_STRING,
		self::TYPE_MODEL_SLUG => self::TYPE_MODEL,
		self::TYPE_MODEL_ARRAY_SLUG => self::TYPE_MODEL_ARRAY,
	];

	protected $name;
	protected $type;
	protected $forbidden = false;

	protected function __construct($name, $data) {
		$this->name = $name;
		$this->type = $data['type'];
		if (isset($data['forbidden'])) {
			$this->forbidden = $data['forbidden'];
		}
	}

	public static function create($name, $data) {
		$info = self::determineType($data);
		$data['type'] = $info[0];
		if ($info[1] !== null) $data['model'] = $info[1];

		switch ($info[0]) {
			case self::TYPE_INTEGER: return new ModelFieldInteger($name, $data);
			case self::TYPE_BOOLEAN: return new ModelFieldBoolean($name, $data);
			case self::TYPE_STRING: return new ModelFieldString($name, $data);
			case self::TYPE_MODEL: return new ModelFieldModel($name, $data);
			case self::TYPE_MODEL_ARRAY: return new ModelFieldModelArray($name, $data);
		}

		return null;
	}

	public function getType() {
		return $this->type;
	}

	public function getSlug() {
		return array_search($this->type, self::TYPES);
	}

	public function getDefault() {
		return null;
	}

	public function isNotNull() {
		return false;
	}

	public function suitableType($value) {
		return false;
	}

	public function normalizeValue($value) {
		return $value;
	}

	public function forbidden() {
		return $this->forbidden;
	}

	public function toStringforbidden() {
		return $this->compileClientString([]);
	}

	protected function compileClientString($data) {
		$data['type'] = "'{$this->getSlug()}'";
		$arr = [];
		foreach ($data as $key => $value) {
			$arr[] = "$key:$value";
		}
		$props = implode(',', $arr);
		return $this->name . ':{' . $props . '}';
	}

	private static function determineType($data) {
		$type = self::TYPE_STRING_SLUG;
		$model = null;
		if (array_key_exists('type', $data)) {
			if (array_key_exists($data['type'], self::TYPES)) {
				$type = $data['type'];
			} else {
				if (preg_match('/\[\]$/', $data['type'])) {
					$type = self::TYPE_MODEL_ARRAY_SLUG;
					$model = substr($data['type'], 0, -2);
				} else {
					$type = self::TYPE_MODEL_SLUG;
					$model = $data['type'];
				}
			}
		}
		return [self::TYPES[$type], $model];
	}
}

