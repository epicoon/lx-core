<?php

namespace lx;

class CheckboxGroup extends LabeledGroup {
	public function preBuild($config) {
		$config = DataObject::create($config);

		if (!$config->unit) $config->unit = [];
		$config->unit['widget'] = Checkbox::class;
		if (!isset($config->unit['labelPosition']))
			$config->unit['labelPosition'] = \lx::RIGHT;

		return parent::preBuild($config);
	}

	public function __construct($config=[]) {
		$config = DataObject::create($config);
		parent::__construct($config);

		if ($config->defaultValue)
			$this->value($config->defaultValue);
	}

	public function value($nums=null) {
		if ($nums === null) {
			$result = [];
			$this->widgets()->each(function($a) use (&$result) {
				if ($a->value())
					$result[] = $a->parent->parent->index;
			});
			return $result;
		}

		$this->widgets()->each(function($a) {
			$a->value(false);
		});
		if (!is_array($nums)) $nums = [$nums];
		foreach ($nums as $num) $this->widget($num)->value(true);
	}	
}
