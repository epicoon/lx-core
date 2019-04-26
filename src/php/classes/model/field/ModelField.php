<?php

namespace lx;

abstract class ModelField {
	const TYPE_INTEGER_SLUG = 'integer';
	const TYPE_BOOLEAN_SLUG = 'boolean';
	const TYPE_STRING_SLUG = 'string';
	const TYPE_TIMESTAMP_SLUG = 'timestamp';

	const TYPE_INTEGER = 1;
	const TYPE_BOOLEAN = 2;
	const TYPE_STRING = 3;
	const TYPE_TIMESTAMP = 4;

	const TYPES = [
		self::TYPE_INTEGER_SLUG => self::TYPE_INTEGER,
		self::TYPE_BOOLEAN_SLUG => self::TYPE_BOOLEAN,
		self::TYPE_STRING_SLUG => self::TYPE_STRING,
		self::TYPE_TIMESTAMP_SLUG => self::TYPE_TIMESTAMP,
	];

	const TYPE_SLUGS = [
		self::TYPE_INTEGER => self::TYPE_INTEGER_SLUG,
		self::TYPE_BOOLEAN => self::TYPE_BOOLEAN_SLUG,
		self::TYPE_STRING => self::TYPE_STRING_SLUG,
		self::TYPE_TIMESTAMP => self::TYPE_TIMESTAMP_SLUG,
	];

	protected $name;
	protected $type;
	protected $isPk = false;
	protected $default = null;
	protected $notNull = false;
	protected $forbidden = false;


	/**************************************************************************************************************************
	 * PUBLIC
	 *************************************************************************************************************************/

	public static function create($name, $data) {
		$info = self::determineType($data);
		$data['type'] = $info[0];
		if ($info[1] !== null) $data['model'] = $info[1];

		switch ($info[0]) {
			case self::TYPE_INTEGER: return new ModelFieldInteger($name, $data);
			case self::TYPE_BOOLEAN: return new ModelFieldBoolean($name, $data);
			case self::TYPE_STRING: return new ModelFieldString($name, $data);
			case self::TYPE_TIMESTAMP: return new ModelFieldTimestamp($name, $data);
		}

		return null;
	}

	public function getDefinition($params = null) {
		if ($params === null) {
			$params = ['pk', 'type', 'dbType', 'default', 'notNull'];
		}

		$result = [];
		if (array_search('pk', $params) !== false && $this->isPk()) {
			//todo - костыльно
			$result['type'] = 'pk';
			return $result;
		}
		if (array_search('type', $params) !== false) {
			$result['type'] = $this->getTypeSlug();
		}
		if (array_search('dbType', $params) !== false) {
			$result['dbType'] = $this->getTypeDb();
		}
		if (array_search('default', $params) !== false) {
			$default = $this->getDefault();
			if ($default !== null) {
				$result['default'] = $default;
			}
		}
		if (array_search('notNull', $params) !== false) {
			$result['notNull'] = $this->isNotNull();
		}
		return $result;
	}

	public function isPk() {
		return $this->isPk;
	}

	public function getType() {
		return $this->type;
	}

	public function getTypeSlug() {
		return self::TYPE_SLUGS[$this->type];
	}

	public function getTypeDb() {
		return $this->getTypeSlug();
	}

	public function getDefault() {
		return $this->default;
	}

	public function isNotNull() {
		return $this->notNull;
	}

	public function isForbidden() {
		return $this->forbidden;
	}

	public function suitableType($value) {
		return false;
	}

	public function normalizeValue($value) {
		return $value;
	}

	public function toStringForClient() {
		$arr = [];

		$default = $this->getDefault();
		if ($default !== null) $arr['default'] = self::valueToString($default);

		return $this->compileClientString($arr);
	}

	/**
	 * Приводит значение к формату, которое можно использовать при формировании строки
	 * */
	public static function valueToString($value) {
		if (is_string($value)) return "'$value'";
		if (is_bool($value)) return $value ? 'true' : 'false';
		if (is_null($value)) return 'null';
		return $value;
	}

	public function toStringForbidden() {
		return $this->compileClientString([]);
	}


	/**************************************************************************************************************************
	 * PROTECTED
	 *************************************************************************************************************************/

	protected function __construct($name, $data) {
		$this->name = $name;
		$this->type = $data['type'];

		if (array_key_exists('pk', $data) && $data['pk'] === true) {
			$this->isPk = true;
		}

		if (array_key_exists('default', $data)) {
			$this->default = $data['default'];
		}

		if (array_key_exists('notNull', $data)) {
			$this->notNull = $data['notNull'];
			if (!array_key_exists('default', $data)) {
				$this->default = static::NOT_NULL_DEFAULT;
			}
		}

		if (isset($data['forbidden'])) {
			$this->forbidden = $data['forbidden'];
		}

		$this->init($data);
	}

	abstract protected function init($data);

	protected function compileClientString($data) {
		$data['type'] = "'{$this->getTypeSlug()}'";
		$arr = [];
		foreach ($data as $key => $value) {
			$arr[] = "$key:$value";
		}
		$props = implode(',', $arr);
		return $this->name . ':{' . $props . '}';
	}


	/**************************************************************************************************************************
	 * PRIVATE
	 *************************************************************************************************************************/

	private static function determineType($data) {
		$type = self::TYPE_STRING_SLUG;
		$model = null;
		if (array_key_exists('type', $data)) {
			if (array_key_exists($data['type'], self::TYPES)) {
				$type = $data['type'];
			}
		}
		return [self::TYPES[$type], $model];
	}
}

