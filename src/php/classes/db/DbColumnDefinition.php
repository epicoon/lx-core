<?php

namespace lx;

/**
 * @deprecated
 * Class DbColumnDefinition
 * @package lx
 */
class DbColumnDefinition {
	public
		$type = null,
		$size = null,
		$isPK = false,
		$isFK = false,
		$notNull = false,
		$default = null;

	public function __construct($conf) {
		foreach ($conf as $prop => $value) {
			if (!property_exists($this, $prop)) continue;

			if ($prop == 'default' && $value !== null) {
				$value = DB::valueForQuery($value);
			}

			if ($prop == 'type' && $value == 'string') {
				$value = 'varchar';
			}

			$this->$prop = $value;
		}
	}
}
