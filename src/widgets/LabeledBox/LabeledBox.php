<?php

namespace lx;

/**
 * @group {i18n:widgets}
 * */
class LabeledBox extends Box {
	public function __construct($config=[]) {
		$config = DataObject::create($config);
		parent::__construct($config);

		$widget = null;
		$label = null;
		if ($config->template) {
			$template = $config->template;
			if (ClassHelper::exists($template[1])) {
				$label = $template[0];
				$widget = $template[1];
			} else {
				$label = $template[1];
				$widget = $template[0];
			}
		}
		if (!$widget) $widget = $config->widget ? $config->widget : Box::class;
		if (!$label) $label = $config->label ? $config->label : '';

		$labelConfig = [
			'parent' => $this,
			'key' => 'label',
			'text' => $label,
		];
		$widgetConfig = $config->widgetConfig ? $config->widgetConfig : [];
		$widgetConfig['key'] = 'widget';

		$labelPosition = $config->labelPosition ? $config->labelPosition : \lx::LEFT;
		if ($labelPosition == \lx::LEFT || $labelPosition == \lx::TOP) {
			new Box($labelConfig);
			$widgetConfig['parent'] = new Box(['parent' => $this, 'key' => 'widgetBox']);
			new $widget($widgetConfig);
		} else {
			$widgetConfig['parent'] = new Box(['parent' => $this, 'key' => 'widgetBox']);
			new $widget($widgetConfig);
			new Box($labelConfig);
		}

		$this->addToPostBuild($config, [
			'labelPosition',
			'labelAlign',
			'widgetAlign',
			'labelSize',
			'widgetSize',
			'events',
		]);
	}

	public function widget() {
		return $this->children['widgetBox']->children['widget'];
	}

	public function labelText() {
		return $this->children['label']->children['text'];
	}

	public function widgetBox() {
		return $this->children['widgetBox'];
	}

	public function label() {
		return $this->children['label'];
	}

	public function value($val=null) {
		$widget = $this->widget();
		if (method_exists($widget, 'value')) {
			if ($val === null) return $widget->value();
			$widget->value($val);
		}
		return null;
	}
}
