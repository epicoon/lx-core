<?php

namespace lx;

class Table extends Box {
	const DEFAULT_ROW_HEIGHT = '25px';

	public function __construct($config=[]) {
		$config = DataObject::create($config);
		parent::__construct($config);

		$rows = $config->rows ? $config->rows : 0;
		$this->cols = $config->cols ? $config->cols : 0;

		$beh;

		if (!$this->heightIsSet())
			$beh = StreamPositioningStrategy::SIZE_BEHAVIOR_BY_CONTENT;
		else if ($config->rowHeight) $beh = StreamPositioningStrategy::SIZE_BEHAVIOR_SIMPLE;
		else if ($rows) $beh = StreamPositioningStrategy::SIZE_BEHAVIOR_PROPORTIONAL;
		else {
			$config->rowHeight = self::DEFAULT_ROW_HEIGHT;
			$beh = StreamPositioningStrategy::SIZE_BEHAVIOR_SIMPLE;
		}

		$this->indents = new IndentData($config->indents ? $config->indents : []);

		$indentData = $this->indents->get();
		$rowStreamConfig = ['sizeBehavior' => $beh];

		if ($indentData['stepY']) $rowStreamConfig['stepY'] = $indentData['stepY'];
		if ($indentData['paddingTop']) $rowStreamConfig['paddingTop'] = $indentData['paddingTop'];
		if ($indentData['paddingBottom']) $rowStreamConfig['paddingBottom'] = $indentData['paddingBottom'];
		if ($config->rowHeight) $rowStreamConfig['defaultSize'] = $config->rowHeight;
		$this->stream($rowStreamConfig);

		if ($config->interactive) $this->addToPostBuild($config, 'interactive');

		if (!$rows || !$this->cols) return;

		$this->insertRows(-1, $rows);
	}


	public function row($row) {
		if (!isset($this->children['r'])) return null;
		if ($this->children['r'] instanceof Vector) return $this->children['r']->at($row);
		return $this->children['r'];
	}

	public function cell($row, $col) {
		$r = $this->row($row);
		if (!$r) return null;
		return $r->cell($col);
	}

	public function rowsCount() {
		if (!isset($this->children['r'])) return 0;
		return $this->children['r']->len;
	}

	public function colsCount($row=null) {
		if ($row === null) return $this->cols;
		if (!isset($this->children['r'])
			|| !$this->children['r']->at($row)
			|| !isset($this->children['r']->at($row)->children['c'])) return 0;
		return $this->children['r']->at($row)->children['c']->len;
	}

	public function rows($r0=0, $r1=null) {
		$c = new Collection();
		$rows = $this->rowsCount();
		if (!$rows) return $c;

		if ($r1 === null || $r1 >= $rows) $r1 = $rows - 1;

		if ($r0 == 0 && $r1 == $rows - 1) return $c->add($this->children['r']);
	
		for ($i=$r0; $i<=$r1; $i++) $c->add( $this->row($i) );
		return $c;
	}

	public function cells($r0=0, $c0=0, $r1=null, $c1=null) {
		$c = new Collection();
		$rows = $this->rowsCount();
		if (!$rows) return $c;

		if ($r1 === null || $r1 >= $rows) $r1 = $rows - 1;

		for ($i=$r0; $i<=$r1; $i++) {
			$r = $this->children['r']->at($i);
			$cols = $this->colsCount($i);
			if ($c1 === null || $c1 >= $cols) $c1 = $cols - 1;

			if ($c0 == 0 && $c1 == $cols - 1) $c->add($r->children['c']);
			else {
				for ($j=$c0; $j<=$c1; $j++)
					$c->add( $this->cell($i, $j) );
			}
		}

		return $c;
	}

	/*
	 * Метод для перебора ячеек "в линию"
	 * для transpon==false (по умолчанию) по строкам, по колонкам
	 * для transpon==true по колонкам, по строкам
	 * */
	public function eachCell($func, $transpon=false, $r0=0, $c0=0, $r1=null, $c1=null) {
		$rows = $this->rowsCount();
		$cols = $this->colsCount();
		$counter = 0;
		if ($r1 === null || $r1 >= $rows) $r1 = $rows - 1;
		if ($c1 === null || $c1 >= $cols) $c1 = $cols - 1;

		if ($transpon) {
			for ($j=$c0; $j<=$c1; $j++)
				for ($i=$r0; $i<=$r1; $i++)
					if ($func($this->cell($i, $j), $i, $j, $counter++) === false)
						return $this;
			return $this;
		}

		for ($i=$r0; $i<=$r1; $i++)
			for ($j=$c0; $j<=$c1; $j++)
				if ($func($this->cell($i, $j), $i, $j, $counter++) === false)
					return $this;
		return $this;
	}

	/*
	 * content - линейный массив, либо двумерный
	 * transpon - по умолчанию false, приоритет строкам, true - приоритет колонкам
	 * r0, c0 - с какой ячейки начинать заполнение
	 * */
	public function setContent($content, $transpon=false, $r0=0, $c0=0) {
		$rows = $this->rowsCount();
		$cols = $this->colsCount();
		$r1;
		$c1;

		if (!is_array($content[0])) $content = [$content];

		if ($transpon) {
			$r1 = $r0 + count($content[0]);
			$c1 = $c0 + count($content) - 1;
		} else {
			$r1 = $r0 + count($content) - 1;
			$c1 = $c0 + count($content[0]);
		}

		$this->eachCell(function($cell, $r, $c) use ($transpon, $content) {
			$cell->text( $transpon ? $content[$c][$r] : $content[$r][$c] );
		}, $transpon, $r0, $c0, $r1, $c1);
	}

	public function insertRows($next, $amt) {
		$cols = $this->colsCount();
		$row = $this->row($next);
		$config = ['key' => 'r'];
		if ($row) $config['before'] = $row;
		else $config['parent'] = $this;

		$indentData = $this->indents->get();
		$colConfig = ['sizeBehavior' => StreamPositioningStrategy::SIZE_BEHAVIOR_PROPORTIONAL];
		if ($indentData['stepX']) $colConfig['stepX'] = $indentData['stepX'];
		if ($indentData['paddingLeft']) $colConfig['paddingLeft']  = $indentData['paddingLeft'];
		if ($indentData['paddingRight']) $colConfig['paddingRight'] = $indentData['paddingRight'];

		$c = TableRow::construct($amt, $config);
		$c->each(function($a) use ($colConfig, $cols) {
			$a->stream($colConfig);
			TableCell::construct($cols, ['parent' => $a, 'key' => 'c']);
		});

		return $c;
	}

	public function setRowCount($rows) {
		if ($rows == $this->rowsCount()) return $this;

		if ($rows < $this->rowsCount()) {
			$this->del('r', $rows, $this->rowsCount() - $rows);
			return;
		}

		return $this->insertRows(-1, $rows - $this->rowsCount());
	}

	public function addRows($amt=1) {
		return $this->setRowCount( $this->rowsCount() + $amt );
	}

	public function delRows($num, $amt) {
		$this->del('r', $num, $amt);
		return $this;
	}

	/*
	 * object.setRowsHeight('30px') - изменит высоту всех строк и запомнит как стандарт для таблицы
	 * */
	public function setRowHeight($height) {
		$this->positioningStrategy->defaultSize = $height;
		if ($this->positioningStrategy->sizeBehavior == StreamPositioningStrategy::SIZE_BEHAVIOR_PROPORTIONAL)
			$this->positioningStrategy->sizeBehavior = StreamPositioningStrategy::SIZE_BEHAVIOR_SIMPLE;

		$this->rows()->each(function($a) {
			$a->height($height);
		});

		return $this;
	}

	// todo
	// setColCount(amt)
	// setColWidth(col, width)
	// merge(r0, c0, r1, c1)

	public function interactive($params=true) {
		if ($params === false) {
			$this->removeFromPostBuild('interactive');
			return $this;
		}

		$this->addToPostBuild(['interactive' => $params]);
		return $this;
	}
}
//=============================================================================================================================

//=============================================================================================================================
class TableRow extends Box {
	public function table() {
		return $this->parent;
	}

	public function cell($num) {
		if (!isset($this->children['c'])) return null;
		if ($this->children['c'] instanceof Vector)
			return $this->children['c']->at($num);
		return $this->children['c'];
	}
}
//=============================================================================================================================

//=============================================================================================================================
class TableCell extends Box {
	public function table() {
		return $this->parent->parent;
	}

	public function row() {
		return $this->parent;
	}

	public function indexes() {
		return [
			$this->parent->index ? $this->parent->index : 0,
			$this->index ? $this->index : 0
		];
	}
}
