<?php

namespace lx;

/**
 * @group {i18n:widgets}
 * */
class RadioGroup extends LabeledGroup {
	public function preBuild($config) {
		$config = DataObject::create($config);

		if (!$config->unit) $config->unit = [];
		$config->unit['widget'] = Radio::class;
		if (!isset($config->unit['labelPosition']))
			$config->unit['labelPosition'] = \lx::RIGHT;

		return parent::preBuild($config);
	}

	public function __construct($config=[]) {
		$config = DataObject::create($config);
		parent::__construct($config);

		$this->value($config->getFirstDefined('defaultValue', 0));
	}

	public function value($num=null) {
		if ($num === null) {
			$result = null;
			$this->widgets()->each(function($a) use (&$result) {
				if ($a->value()) {
					$result = $a->parent->parent->index;
					$this->stop();
				}
			});
			return $result;
		}

		if ($num > $this->childrenCount('unit') - 1) return;

		$this->widgets()->each(function($a) {
			$a->value(false);
		});
		$this->widget($num)->value(true);
	}
}
