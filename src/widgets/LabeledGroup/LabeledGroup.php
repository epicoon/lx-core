<?php

namespace lx;

/**
 * @group {i18n:widgets}
 * */
class LabeledGroup extends Box {
	/**
	 * params = [
	 *	// стандартные для Box,
	 *	
	 *	cols: integer
	 *	grid: {} | slot: {}
	 *	unit: {}  // конфиг для единицы группы
	 *	labels: []
	 * ]
	 * */
	public function __construct($config = []) {
		$config = DataObject::create($config);
		parent::__construct($config);

		$grid = null;
		$labels = $config->labels;
		$units = $config->units;
		$unitConfig = $config->unit ? $config->unit : [];

		if ($config->slot) {
			$config->slot['slotsCount'] = 0;
			$this->slot($config->slot);
		} else {
			$grid = $config->grid ? $config->grid : [];
			if (!isset($grid['cols'])) $grid['cols'] = $config->cols ? $config->cols : 1;
			$this->grid($grid);
		}

		$unitConfig['parent'] = $this;
		$unitConfig['key'] = 'unit';
		$unitConfig['width'] = 1;

		if ($labels)
			foreach ($labels as $label) {
				$unitConfig['label'] = $label;
				new LabeledBox($unitConfig);
			}

		if ($units)
			foreach ($units as $unit) {
				$unitConfig = array_merge($unitConfig, $unit);
				new LabeledBox($unitConfig);
			}
	}

	public function units() {
		$units = $this->children['unit'];
		return new Collection($units);
	}

	public function widgets() {
		$c = new Collection();
		$this->units()->each(function($a) use ($c) {
			$c->add($a->widget());
		});
		return $c;
	}

	public function labels() {
		$c = new Collection();
		$this->units()->each(function($a) use ($c) {
			$c->add($a->label());
		});
		return $c;
	}

	public function labelTexts() {
		$c = new Collection();
		$this->units()->each(function($a) use ($c) {
			$c->add($a->labelText());
		});
		return $c;
	}

	public function unit($num) {
		$units = $this->children['unit'];
		if (!$units) return null;
		if ($units instanceof Vector) return $units->at($num);
		return $units;
	}

	public function widget($num) {
		$unit = $this->unit($num);
		if (!$unit) return null;
		return $unit->widget();
	}

	public function label($num) {
		$unit = $this->unit($num);
		if (!$unit) return null;
		return $unit->label();
	}
}
