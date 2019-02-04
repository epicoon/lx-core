<?php

namespace lx;

abstract class ModelFieldSimple extends ModelField {
	protected $default = null;
	protected $notNull = false;

	protected function __construct($name, $data) {
		parent::__construct($name, $data);

		if (array_key_exists('notNull', $data)) {
			$this->notNull = $data['notNull'];
			if (!array_key_exists('default', $data)) {
				$this->default = static::NOT_NULL_DEFAULT;
			}
		}

		if (array_key_exists('default', $data)) {
			$this->default = $data['default'];
		}

		$this->init($data);
	}

	public function getDefault() {
		return $this->default;
	}

	public function isNotNull() {
		return $this->notNull;
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

	abstract protected function init($data);
}
