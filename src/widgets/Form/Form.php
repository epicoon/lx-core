<?php

namespace lx;

/**
 * @group {i18n:widgets}
 * */
class Form extends Box {
	public function fields($list) {
		foreach ($list as $key => $item) {
			$config = [];
			if (is_array($item)) {
				$widget = $item[0];
				$config = $item[1];
			} else $widget = $item;
			$this->field($key, $widget, $config);
		}
	}

	public function field($fieldName, $instance, $config) {
		$config = DataObject::create($config);
		if ($config->after && $config->after->parent !== $this) $config->extract('after');
		if ($config->before && $config->before->parent !== $this) $config->extract('before');
		$config->parent = $this;
		$config->field = $fieldName;

		$config->key = $fieldName;
		$elem = new $instance($config);
	}

	public function labeledFields($list) {
		foreach ($list as $key => $item) $this->labeledField($key, $item);
	}

	public function labeledField($name, $config) {
		$fieldConfig = [];
		if (is_array($config)) {
			if (count($config > 2)) $fieldConfig = $config[2];
			if (ClassHelper::exists($config[0])) {
				if (!isset($fieldConfig['labelOrientation'])) $fieldConfig['labelOrientation'] = \lx::RIGHT;
				$fieldConfig['widget'] = $config[0];
				$fieldConfig['label'] = $config[1];
			} else {
				$fieldConfig['label'] = $config[0];
				$fieldConfig['widget'] = $config[1];
			}
		}
		$this->field($name, LabeledBox::className(), $fieldConfig);
	}
}
