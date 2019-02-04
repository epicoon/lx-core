<?php

namespace lx;

class IndentData extends DataObject {
	public function __construct($config=[]) {
		$this->set($config);
	}

	//todo отптимизировать упаковку-распаковку
	public function pack() {
		$indents = $this->get();
		return 'i:' . implode(',', $indents['step']) . ',' . implode(',', $indents['padding'][0]) . ',' . implode(',', $indents['padding'][1]);
	}

	public static function createOrNull($config) {
		$result = new self($config);
		if ($result->isEmpty()) return null;
		return $result;
	}

	public function set($config) {
		$config = DataObject::create($config);

		if ($config->indent       ) $this->indent        = $config->indent;
		if ($config->step         ) $this->step          = $config->step;
		if ($config->stepX        ) $this->stepX         = $config->stepX;
		if ($config->stepY        ) $this->stepY         = $config->stepY;
		if ($config->padding      ) $this->padding       = $config->padding;
		if ($config->paddingX     ) $this->paddingX      = $config->paddingX;
		if ($config->paddingY     ) $this->paddingY      = $config->paddingY;
		if ($config->paddingLeft  ) $this->paddingLeft   = $config->paddingLeft;
		if ($config->paddingRight ) $this->paddingRight  = $config->paddingRight;
		if ($config->paddingTop   ) $this->paddingTop    = $config->paddingTop;
		if ($config->paddingBottom) $this->paddingBottom = $config->paddingBottom;
	}

	public function get() {
		$default = 0;
		$step = [
			$this->getFirstDefined(['stepX', 'step', 'indent'], $default),
			$this->getFirstDefined(['stepY', 'step', 'indent'], $default),
		];
		$padding = [
			[
				$this->getFirstDefined(['paddingLeft', 'paddingX', 'padding', 'indent'], $default),
				$this->getFirstDefined(['paddingRight', 'paddingX', 'padding', 'indent'], $default),
			],
			[
				$this->getFirstDefined(['paddingTop', 'paddingY', 'padding', 'indent'], $default),
				$this->getFirstDefined(['paddingBottom', 'paddingY', 'padding', 'indent'], $default),
			]
		]; 

		return [
			'step' => $step,
			'padding' => $padding,
			'stepX' => $step[0],
			'stepY' => $step[1],
			'paddingLeft' => $padding[0][0],
			'paddingRight' => $padding[0][1],
			'paddingTop' => $padding[1][0],
			'paddingBottom' => $padding[1][1]
		];
	}

	public static function getZero() {
		return [
			'step' => [0, 0],
			'padding' => [[0, 0], [0, 0]],
			'stepX' => 0,
			'stepY' => 0,
			'paddingLeft' => 0,
			'paddingRight' => 0,
			'paddingTop' => 0,
			'paddingBottom' => 0
		];
	}
}
