<?php

namespace lx;

class PositioningStrategy extends DataObject {
	const
		FORMAT_PERCENT = 1,
		FORMAT_PX = 2,
		FORMAT_FREE = 3;

	protected
		$defaultFormat,
		$innerFormat,
		$owner,
		$indents,

		$needJsActualize;

	public function __construct($owner, $config=[]) {
		$this->owner = $owner;

		$this->defaultFormat = self::FORMAT_PERCENT;
		$this->innerFormat = self::FORMAT_FREE;

		$this->needJsActualize = false;
	}

	public function allocate($elem, $config=[]) {
		$geom = $this->geomFromConfig($config);

		if (!isset($geom['w'])) {
			if (!isset($geom['l'])) $geom['l'] = '0px';
			if (!isset($geom['r'])) $geom['r'] = '0px';
		}

		if (!isset($geom['h'])) {
			if (!isset($geom['t'])) $geom['t'] = '0px';
			if (!isset($geom['b'])) $geom['b'] = '0px';
		}

		if (isset($geom['r'])) $elem->setGeomBuffer(\lx::RIGHT,  $geom['r']);
		if (isset($geom['b'])) $elem->setGeomBuffer(\lx::BOTTOM, $geom['b']);

		if (isset($geom['w'])) $elem->setGeomBuffer(\lx::WIDTH,  $geom['w']);
		if (isset($geom['h'])) $elem->setGeomBuffer(\lx::HEIGHT, $geom['h']);

		if (isset($geom['l'])) $elem->setGeomBuffer(\lx::LEFT,   $geom['l']);
		if (isset($geom['t'])) $elem->setGeomBuffer(\lx::TOP,    $geom['t']);
	}

	public function tryReposition($elem, $param, $val) {
		$elem->setGeomParam($param, $val);
		return true;		
	}

	public function actualize() {
		$format = $this->getFormatText($this->innerFormat);
		$setParam = function($elem, $param, $val) use ($format) {
			if (is_numeric($val)) $val .= $format;
			$elem->setGeomParam($param, $val);			
		};
		$this->owner->_children->each(function($a) use ($setParam) {
			$geom = $a->geomBuffer;

			if (array_key_exists(\lx::RIGHT, $geom)) $setParam($a, \lx::RIGHT, $geom[\lx::RIGHT]);
			if (array_key_exists(\lx::BOTTOM, $geom)) $setParam($a, \lx::BOTTOM, $geom[\lx::BOTTOM]);

			if (array_key_exists(\lx::WIDTH, $geom)) $setParam($a, \lx::WIDTH, $geom[\lx::WIDTH]);
			if (array_key_exists(\lx::HEIGHT, $geom)) $setParam($a, \lx::HEIGHT, $geom[\lx::HEIGHT]);

			if (array_key_exists(\lx::LEFT, $geom)) $setParam($a, \lx::LEFT, $geom[\lx::LEFT]);
			if (array_key_exists(\lx::TOP, $geom)) $setParam($a, \lx::TOP, $geom[\lx::TOP]);

			$a->located = true;
		});
	}

	public function clear() {

	}

	public function pack() {
		$str = "{$this->className(false)};df:{$this->defaultFormat};if:{$this->innerFormat}";
		if ($this->needJsActualize) $str .= ";na:1";
		$i = $this->packIndents();
		if ($i) $str .= ";$i";
		return $str;
	}

	protected function packIndents() {
		if (!$this->indents) return false;
		return $this->indents->pack();
	}

	protected function setSavedParam($elem, $param, $val) {
		if (!$elem->pos) $elem->pos = [];
		$elem->pos[$param] = $val;
	}

	/**
	 * Преобразования типа PositioningStrategy::FORMAT_PERCENT => '%'
	 * */
	protected function getFormatText($format) {
		if ($format == self::FORMAT_FREE)
			$format = $this->innerFormat == self::FORMAT_FREE
				? $this->defaultFormat
				: $this->innerFormat;
		if ($format == self::FORMAT_PERCENT) return '%';
		if ($format == self::FORMAT_PX) return 'px';
		return '';
	}

	protected function getGeomParam($elem, $param, $format=null) {

		/*
		//todo воткнул какой-то костыль - долго разбираться, но надо. Параметр не определен
		Ситуация:
		пропорциональный поток
		в элемент потока (сам элемент без стратегий) добавляется потомок без указания геометрии
		*/
		//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
		if (!isset($elem->geomBuffer[$param])) {
			return '0px';
		}
		//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!


		$val = $elem->geomBuffer[$param];
		if (is_numeric($val)) {
			if ($format === null) $format = $this->getFormatText($this->innerFormat);
			$val .= $format;
		}
		return $val;
	}

	/**
	 * Можно задать настройки для отступов
	 * */
	protected function setIndents($config=[]) {
		$this->indents = IndentData::createOrNull($config);
	}

	/**
	 * Если настройки для отступов не заданы, будет возвращен полноценный объект настроек, заполненный нулями
	 * */
	public function getIndents() {
		return $this->indents
			? $this->indents->get()
			: IndentData::getZero();
	}

	/**
	 * Извлекает из конфигурации позиционные параметры
	 * */
	public function geomFromConfig($config) {
		$config = DataObject::create($config);
		$geom = [];

		if ($config->margin) $config->geom = [
			$config->margin,
			$config->margin,
			null,
			null,
			$config->margin,
			$config->margin
		];
		if ($config->geom) {
			$geom['l'] = $config->geom[0];
			$geom['t'] = $config->geom[1];
			$geom['w'] = $config->geom[2];
			$geom['h'] = $config->geom[3];
			if (count($config->geom) > 4) $geom['r'] = $config->geom[4];
			if (count($config->geom) > 5) $geom['b'] = $config->geom[5];
		}
		if ($config->coords) {
			$geom['l'] = $config->coords[0];
			$geom['t'] = $config->coords[1];
			if (count($config->coords) > 2) $geom['r'] = $config->coords[2];
			if (count($config->coords) > 3) $geom['b'] = $config->coords[3];
		}
		if ($config->size) {
			$geom['w'] = $config->size[0];
			$geom['h'] = $config->size[1];
		}
		if ($config->left   !== null) $geom['l'] = $config->left;
		if ($config->top    !== null) $geom['t'] = $config->top;
		if ($config->right  !== null) $geom['r'] = $config->right;
		if ($config->bottom !== null) $geom['b'] = $config->bottom;
		if ($config->width  !== null) $geom['w'] = $config->width;
		if ($config->height !== null) $geom['h'] = $config->height;

		foreach ($geom as $key => $param)
			if ($param === null) unset($geom[$key]);

		return $geom;
	}
}
