<?php

namespace lx;

class GridPositioningStrategy extends PositioningStrategy {
	const
		SIZE_BEHAVIOR_SCROLLING = 1,
		SIZE_BEHAVIOR_BY_CONTENT = 2,
		SIZE_BEHAVIOR_PROPORTIONAL = 3,
		SIZE_BEHAVIOR_PROPORTIONAL_CONST = 4,

		PADDING = '0px',
		STEP = '0px',
		ROW_HEIGHT = '25px',
		COLS = 12,

		HEIGHT_BEHAVIOR = 2;

	public function __construct($owner, $config=[]) {
		parent::__construct($owner, $config);
		$config = DataObject::create($config);

		$this->defaultFormat = self::FORMAT_PX;
		$this->innerFormat = self::FORMAT_PX;

		if ($config->hasProperties()) $this->init($config);
	}

	public function init($config) {
		$config = DataObject::create($config);

		$this->rowHeight = $config->rowHeight ? $config->rowHeight : self::ROW_HEIGHT;
		$this->cols = $config->cols ? $config->cols : self::COLS;
		$this->sizeBehavior = $config->sizeBehavior ? $config->sizeBehavior : self::HEIGHT_BEHAVIOR;

		if ($this->sizeBehavior == self::SIZE_BEHAVIOR_PROPORTIONAL_CONST) {
			$this->rows = $config->rows ? $config->rows : 1;
			$this->map = Vector::createLen($this->rows);
		} else $this->map = Vector::createLen(1);

		if (!$config->indent) {
			if (!$config->padding) $config->padding = self::PADDING;
			if (!$config->step) $config->step = self::STEP;			
		}

		$this->setIndents($config);
	}

	public function pack() {
		$str = parent::pack();

		$str .= ";c:{$this->cols};hb:{$this->sizeBehavior};m:" . $this->map->join(',');

		if ($this->rowHeight != self::ROW_HEIGHT)
			$str .= ";rh:{$this->rowHeight}";
		if ($this->rows !== null)
			$str .= ";r:{$this->rows}";

		return $str;
	}

	public function allocate($elem, $config=[]) {
		$gc = GridCalculator::create($this);
		$gc->toGrid($elem, $config);
	}

	public function tryReposition($elem, $param, $val) {
		$elem->inGrid[$param] = $val;
	}

	public function reset() {
		$this->owner->getChildren()->each(function($a) {
			$a->inGrid = null;
		});
		$this->map = Vector::createLen(1);
	}

	public function actualize() {
		//todo местные расчеты
		$this->needJsActualize = true;
	}
}

//=============================================================================================================================

//=============================================================================================================================
/**
 * @hidden
 * */
class GridCalculator {
	protected
		$grid;

	public static function create($grid) {
		$obj;
		switch ($grid->sizeBehavior) {
			case GridPositioningStrategy::SIZE_BEHAVIOR_BY_CONTENT: $obj = new BoxGridCalculator(); break;
			case GridPositioningStrategy::SIZE_BEHAVIOR_PROPORTIONAL: $obj = new RowsGridCalculator(); break;
			case GridPositioningStrategy::SIZE_BEHAVIOR_PROPORTIONAL_CONST: $obj = new ConstGridCalculator(); break;
		}
		$obj->grid = $grid;
		return $obj;
	}

	public function toGrid($elem, $config) {
		$geom = $this->grid->geomFromConfig($config);
		$geom = $this->normalizeGeomH($geom);
		$geom = $this->normalizeGeomV($geom);
		$elem->inGrid = [];
		$elem->inGrid[\lx::LEFT] = $geom['l'];
		$elem->inGrid[\lx::TOP] = $geom['t'];
		$elem->inGrid[\lx::WIDTH] = $geom['w'];
		$elem->inGrid[\lx::HEIGHT] = $geom['h'];

		$grid = $this->grid;
		for ($i=$geom['t'], $l=$geom['t']+$geom['h']; $i<$l; $i++) {
			$lim = $geom['l'] + $geom['w'];
			if ($lim > $grid->map->at($i))
				$grid->map->set($i, $lim);
		}
	}

	protected function normalizeGeomV($geom) {
		return $geom;
	}

	protected function normalizeGeomH($geom) {
		$grid = $this->grid;

		// для ситуации, когда задан right
		$definedCount = (int)(isset($geom['w'])) + (int)(isset($geom['l'])) + (int)(isset($geom['r']));
		if ($definedCount == 3) $geom['r'] = null;
		if (isset($geom['r'])) {
			if ($definedCount == 1) {
				$geom['l'] = 0;
				$geom['w'] = $grid->cols - $geom['r'];
			} else {
				if (isset($geom['l'])) $geom['w'] = $grid->cols - $geom['l'] - $geom['r'];
				else $geom['l'] = $grid->cols - $geom['w'] - $geom['r'];
			}
			unset($geom['r']);
		}

		// по ширине не вылазим
		if (!isset($geom['w']) || $geom['w'] > $grid->cols) $geom['w'] = $grid->cols;
		return $geom;
	}
}
//=============================================================================================================================

//=============================================================================================================================
/**
 * @hidden
 * */
class ConstGridCalculator extends GridCalculator {
	protected function normalizeGeomV($geom) {
		$grid = $this->grid;

		// по высоте корректируем
		if (!isset($geom['h'])) $geom['h'] = 1;
		if ($geom['h'] > $grid->rows) $geom['h'] = $grid->rows;

		// ищем последнюю строку, которую займет элемент
		$row;
		if (isset($geom['t'])) $row = $geom['t'] + $geom['h'] - 1;
		else {
			$row = $this->findLine($geom);
			if ($row === false) {
				// todo ??? тут бы надо исключение - типа не влезло
				$row = 1;
				$geom['t'] = 0;
			} else $geom['t'] = $row + 1 - $geom['h'];
		}
		$geom['l'] = isset($geom['l'])
			? $geom['l']
			: $grid->map->maxOnRange($geom['t'], $row);
		return $geom;
	}

	protected function findLine($geom) {
		$grid = $this->grid;
		$okRows = 0;
		for ($i=0, $l=$grid->map->len; $i<$l; $i++)
			if ($grid->cols - $grid->map->at($i) >= $geom['w']) {
				$okRows++;
				if ($okRows == $geom['h']) return $i;
			}
		return false;
	}
}
//=============================================================================================================================

//=============================================================================================================================
/**
 * @hidden
 * */
class RowsGridCalculator extends ConstGridCalculator {
	protected function normalizeGeomV($geom) {
		$grid = $this->grid;

		// по высоте корректируем
		if (!isset($geom['h'])) $geom['h'] = 1;

		// ищем последнюю строку, которую займет элемент
		$row;
		if (isset($geom['t'])) $row = $geom['t'] + $geom['h'] - 1;
		else {
			$row = $this->findLine($geom);
			$geom['t'] = $row + 1 - $geom['h'];
		}

		// актуализируем сетку в высоту
		if ($row+1 > $grid->map->len)
			for ($i=0, $l=$row+1-$grid->map->len; $i<$l; $i++)
				$this->addLine();

		// левая позиция может быть указана, иначе - справа от уже существующих элементов
		$geom['l'] = isset($geom['l'])
			? $geom['l']
			: $this->grid->map->maxOnRange($geom['t'], $row);
		return $geom;
	}

	protected function findLine($geom) {
		$result = parent::findLine($geom);
		if ($result) return $result;

		// если не было найдено место в существующей карте - надо расширять карту
		$limit = $geom['h'];
		$counter = 0;
		while (!$result) {
			$this->addLine();
			$result = parent::findLine($geom);
			$counter++;
			if ($counter > $limit) {
				break;
			}
		}

		//todo из-за странного поведения - не всегда находит результат - такой вот костыль, вырезающий переросшую карту
		$i = $i=$this->grid->map->len-1;
		$done = false;
		while ($i >= 0) {
			if ($this->grid->map->at($i) == 0) {				
				$this->grid->map->pop();
			} else {
				break;
			}
			$i--;
		}

		return $result;
	}

	protected function addLine() {
		$this->grid->map->push(0);
	}
}
//=============================================================================================================================

//=============================================================================================================================
/**
 * @hidden
 * */
class BoxGridCalculator extends RowsGridCalculator {
}
//=============================================================================================================================
