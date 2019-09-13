<?php

namespace lx;

class BitMap {
	private $_x;
	private $_y;
	private $map;

	public function __construct($x = 0, $y = 0) {
		$this->_x = $x;
		$this->_y = $y;
		if ($y) {
			$this->reset();
		} else {
			$this->map = [];
		}
	}

	public function __get($name) {
		switch ($name) {
			case 'x': return $this->_x;
			case 'y': return $this->_y;
		}
		return null;
	}

	public static function createFromString($str) {
		$arr = preg_split('/\s+/', $str);
		$x = strlen($arr[0]);
		$y = count($arr);
		$map = new self($x, $y);
		foreach ($arr as $i => $line) {
			for ($j=0; $j<$x; $j++) {
				if ((int)$line{$j}) $map->setBit($j, $i);
			}
		}

		return $map;
	}

	public function reset() {
		$this->map = array_fill(0, $this->_y, 0);
		foreach ($this->map as &$item) {
			$item = new BitLine($this->_x);
		}
		unset($item);
	}

	public function setX($amt) {
		if ($this->_x == $amt) return;
		foreach ($this->map as $line) {
			$line->setLen($amt);
		}
		$this->_x = $amt;
	}

	public function addX() {
		$this->setX($this->_x + 1);
	}

	public function dropX() {
		$this->setX($this->_x - 1);
	}

	public function setY($amt) {
		if ($this->_y == $amt) return;
		if ($this->_y > $amt) {
			$this->map = array_slice($this->map, 0, $amt);
		} else {
			$len = count($this->map);
			for ($i=$len; $i<$amt; $i++) {
				$this->map[] = new BitLine($this->_x);
			}
		}
		$this->_y = $amt;
	}

	public function addY() {
		$this->setY($this->_y + 1);
	}

	public function dropY() {
		$this->setY($this->_y - 1);
	}

	public function setBit($x, $y) {
		if ($x >= $this->_x || $y >= $this->_y) return;
		$this->map[$y]->setBit($x);
	}

	public function unsetBit($x, $y) {
		if ($x >= $this->_x || $y >= $this->_y) return;
		$this->map[$y]->unsetBit($x);
	}

	public function getBit($x, $y) {
		if ($x >= $this->_x || $y >= $this->_y) return null;
		return $this->map[$y]->getBit($x);
	}

	public function getLine($y) {
		if ($y >= $this->_y) return null;
		return $this->map[$y];
	}

	public function slice($shift, $amt) {
		if (!$amt) return null;
		if ($shift + $amt > $this->_y) {
			$amt = $this->_y - $shift;
		}
		if ($amt < 1) return null;
		$result = new BitMap();
		$result->_x = $this->_x;
		$result->_y = $amt;
		$result->map = array_slice($this->map, $shift, $amt);
		return $result;
	}

	public function findSpace($w, $h) {
		if ($h > $this->_y || !$h) return false;

		$rowIndex = 0;
		while (true) {
			if ($rowIndex + $h > $this->_y) return false;
			$row = $this->map[$rowIndex];
			$slice = $this->slice($rowIndex + 1, $h - 1);
			$projection = $slice ? $row->project($slice->map) : $row;
			$shift = $projection->findSpace($w);
			if ($shift !== false) return [$shift, $rowIndex];
			$rowIndex++;
		}
	}

	public function setSpace($x, $y=null, $w=null, $h=null) {
		if (is_array($x)) {
			$this->setSpace($x[0], $x[1], $x[2], $x[3]);
			return;
		}
		if ($x + $w > $this->_x) $w = $this->_x - $x;
		if ($w < 1) return;
		if ($y + $h > $this->_y) $h = $this->_y - $y;
		if ($h < 1) return;

		for ($i=$y, $l=$y+$h; $i<$l; $i++) {
			$this->map[$i]->setBit($x, $w);
		}
	}

	public function unsetSpace($x, $y=null, $w=null, $h=null) {
		if (is_array($x)) {
			$this->unsetSpace($x[0], $x[1], $x[2], $x[3]);
			return;
		}
		if ($x + $w > $this->_x) $w = $this->_x - $x;
		if ($w < 1) return;
		if ($y + $h > $this->_y) $h = $this->_y - $y;
		if ($h < 1) return;

		for ($i=$y, $l=$y+$h; $i<$l; $i++) {
			$this->map[$i]->unsetBit($x, $w);
		}
	}

	public function toString() {
		$arr = [];
		foreach ($this->map as $line) {
			$arr[] = $line->toString();
		}

		return implode(PHP_EOL, $arr);
	}
}
