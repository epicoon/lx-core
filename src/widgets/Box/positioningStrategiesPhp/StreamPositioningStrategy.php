<?php

namespace lx;

class StreamPositioningStrategy extends PositioningStrategy {
	const
		SIZE_BEHAVIOR_SIMPLE = 1,
		SIZE_BEHAVIOR_BY_CONTENT = 2,
		SIZE_BEHAVIOR_PROPORTIONAL = 3,
		DEFAULT_SIZE = '25px';

	protected
		$sizeBehavior,
		$defaultSize,
		$direction;

	public function __construct($owner, $config=[]) {
		parent::__construct($owner, $config);
		$config = DataObject::create($config);

		$this->defaultFormat = self::FORMAT_PX;
		$this->innerFormat = self::FORMAT_PX;

		if ($config->hasProperties()) $this->init($config);
	}

	public function init($config) {
		if (is_numeric($config)) $config = [ 'direction' => $config ];
		$config = DataObject::create($config);
		$this->sizeBehavior = $config->sizeBehavior
			? $config->sizeBehavior
			: self::SIZE_BEHAVIOR_SIMPLE;

		// не для пропорционального поведения - если будут добавляться элементы без размера, или с числом - используется это значение
		$this->defaultSize = $config->defaultSize
			? $config->defaultSize
			: self::DEFAULT_SIZE;

		if (!$config->direction)
			$config->direction = ($this->owner->parent && $this->owner->parent->streamDirection() === \lx::VERTICAL)
				? \lx::HORIZONTAL 
				: \lx::VERTICAL;
		
		$this->direction = $config->direction;
		if ($this->sizeBehavior == self::SIZE_BEHAVIOR_SIMPLE) {
			if ($this->direction == \lx::VERTICAL)
				$this->owner->style('overflow-y', 'auto');
			else
				$this->owner->style('overflow-x', 'auto');
		} else $this->owner->style('overflow', 'hidden');

		$this->setIndents($config);
		if ($config->lock) $this->lock = $config->lock;

		return $this;
	}

	public function pack() {
		$str = parent::pack();
		$str .= ";sb:{$this->sizeBehavior};ds:{$this->defaultSize};d:{$this->direction}";
		if ($this->lock) $str .= ";l:{$this->lock}";

		return $str;
	}

	public function allocate($elem, $config=[]) {
		// выставление боковых отступов
		$indents = $this->indentsByDirection();
		extract($indents);
		/**
		 * @var $size0
		 * @var $size1
		 * @var $padding0
		 * @var $padding1
		 * */
		$elem->style[Geom::geomName($size0)] = $padding0;
		$elem->style[Geom::geomName($size1)] = $padding1;

		// разбор переданной геометрии
		$geom = $this->geomFromConfig($config);

		$size = $this->direction == \lx::VERTICAL
			? (isset($geom['h']) ? $geom['h'] : 1)
			: (isset($geom['w']) ? $geom['w'] : 1);
		$sizeConst = $this->direction == \lx::VERTICAL ? \lx::HEIGHT : \lx::WIDTH;

		if (is_numeric($size)) {
			if ($this->sizeBehavior == self::SIZE_BEHAVIOR_PROPORTIONAL) {
				$elem->streamProportion = $size;
				return;
			}

			$this->setSavedParam($elem, $sizeConst, $size);
		} else {
			$this->setSavedParam($elem, $sizeConst, $size);
			//todo разбираться что это все такое
			// $elem->setGeomBuffer($sizeConst, $size);
		}
	}

	public function tryReposition($elem, $param, $val) {
		if ($this->direction == \lx::VERTICAL && $param != \lx::HEIGHT) return false;
		if ($this->direction == \lx::HORIZONTAL && $param != \lx::WIDTH) return false;

		$elem->setGeomBuffer($param, $val);

		return true;
	}

	public function actualize() {
		if ($this->sizeBehavior == self::SIZE_BEHAVIOR_PROPORTIONAL) {
			$this->needJsActualize = true;
			return;
		}

		if ($this->sizeBehavior == self::SIZE_BEHAVIOR_BY_CONTENT) {
			$this->needJsActualize = true;
		}

		$elem = $this->owner->_children->first();
		if (!$elem) return;
		$prev = null;

		$indents = $this->indentsByDirection();
		extract($indents);
		/**
		 * @var $padding
		 * @var $step
		 * @var $pos
		 * @var $size
		 * */

		$format = $this->getFormatText($this->innerFormat);
		$defaultSizeSplitted = Geom::splitGeomValue($this->defaultSize);
		$defaultSizeValue = $defaultSizeSplitted[0];
		$defaultSizeFormat = $defaultSizeSplitted[1];
		while ($elem) {
			$value = isset($elem->geomBuffer[$size]) ? $elem->geomBuffer[$size] : 1;
			if (is_numeric($value)) {
				//todo - подход меняется, числовое значение будет значит количество дефолтных высот. Надо к этой идее всё привести и тут и на js.
				//todo и с другими стратегиями посмотреть - чтобы всё в одной логике было
				// $value .= $format;
				$value = ($value * $defaultSizeValue) . $defaultSizeFormat;
			}
			$elem->style[Geom::geomName($size)] = $value;

			if (!$this->needJsActualize) {
				$pre = $prev
					? Geom::calculate('a + b + c', [
						'a' => $prev->getGeomParam($pos),
						'b' => $prev->getGeomParam($size),
						'c' => $step
					])
					: $padding;

				if ($pre === false) $this->needJsActualize = true;
				else {
					$elem->style[Geom::geomName($pos)] = $pre;
					$elem->located = true;
				}
			}

			$prev = $elem;
			$elem = $elem->nextSibling();
		}
	}

	protected function indentsByDirection() {
		$result = [];

		$indents = $this->getIndents();
		if ($this->direction == \lx::VERTICAL) {
			$result['padding'] = $indents['paddingTop'];
			$result['step'] = $indents['stepY'];
			$result['padding0'] = $indents['paddingLeft'];
			$result['padding1'] = $indents['paddingRight'];
			$result['pos'] = \lx::TOP;
			$result['size'] = \lx::HEIGHT;
			$result['size0'] = \lx::LEFT;
			$result['size1'] = \lx::RIGHT;
		} else {
			$result['padding'] = $indents['paddingLeft'];
			$result['step'] = $indents['stepX'];
			$result['padding0'] = $indents['paddingTop'];
			$result['padding1'] = $indents['paddingBottom'];
			$result['pos'] = \lx::LEFT;
			$result['size'] = \lx::WIDTH;
			$result['size0'] = \lx::TOP;
			$result['size1'] = \lx::BOTTOM;
		}
		$format = $this->getFormatText($this->innerFormat);
		if (is_numeric($result['padding']))  $result['padding'] .= $format;
		if (is_numeric($result['step']))     $result['step'] .= $format;
		if (is_numeric($result['padding0'])) $result['padding0'] .= $format;
		if (is_numeric($result['padding1'])) $result['padding1'] .= $format;

		return $result;
	}
}
