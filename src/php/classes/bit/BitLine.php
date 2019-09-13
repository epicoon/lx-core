<?php

namespace lx;

class BitLine {
	const BASIS = 32;
	const BIT = [
		1, 2, 4, 8, 16, 32, 64, 128, 256, 512, 1024, 2048,
		4096, 8192, 16384, 32768,

		65536, 131072, 262144, 524288, 1048576, 2097152,
		4194304, 8388608, 16777216, 33554432, 67108864,
		134217728, 268435456, 536870912, 1073741824, 2147483648
	];

	private $_len;
	private $_innerLen;
	private $_map;

	public function __construct($len) {
		$this->_len = $len ? $len : self::BASIS;
		$this->_innerLen = floor(($this->_len - 1) / self::BASIS) + 1;
		$this->_map = array_fill(0, $this->_innerLen, 0);
	}

	public function __get($name) {
		switch ($name) {
			case 'len': return $this->_len;
			case 'map': return $this->_map;
		}

		return null;
	}

	public function setLen($len) {
		if ($len == $this->_len) return;

		$this->_len = $len ? $len : self::BASIS;
		$this->_innerLen = floor(($this->_len - 1) / self::BASIS) + 1;
		$size = count($this->_map);
		if ($this->_innerLen == $size) return;
		if ($this->_innerLen < $size) {
			$this->_map = array_slice($this->_map, 0, $this->_innerLen);
		} else {
			for ($i=$size; $i<$this->_innerLen; $i++) $this->_map[] = 0;
		}
	}

	public function setBit($i, $amt = 1) {
		$innerIndex = floor($i / self::BASIS);
		$index = $i % self::BASIS;
		$len = count($this->_map);

		for ($k=0; $k<$amt; $k++) {
			$this->_map[$innerIndex] = $this->_map[$innerIndex] | self::BIT[$index];
			$index++;
			if ($index == self::BASIS) {
				$innerIndex++;
				if ($innerIndex == $len) break;
				$index = 0;
			}
		}
	}

	public function unsetBit($i, $amt = 1) {
		$innerIndex = floor($i / self::BASIS);
		$index = $i % self::BASIS;
		$len = count($this->_map);

		for ($k=0; $k<$amt; $k++) {
			$this->_map[$innerIndex] = $this->_map[$innerIndex] & ~self::BIT[$index];
			$index++;
			if ($index == self::BASIS) {
				$innerIndex++;
				if ($innerIndex == $len) break;
				$index = 0;
			}
		}
	}

	public function getBit($i) {
		$innerIndex = floor($i / self::BASIS);
		$index = $i % self::BASIS;
		return (int)((bool) ($this->_map[$innerIndex] & self::BIT[$index]) );
	}

	public function clone() {
		$result = new BitLine($this->_len);

		foreach ($this->_map as $i => $item) {
			$result->_map[$i] = $item;
		}

		return $result;
	}

	public function copy($line) {
		$this->setLen($line->len);
		foreach ($line->_map as $i => $item) {
			$this->_map[$i] = $item;
		}
	}

	public function project($line) {
		$result = $this->clone();
		if (is_array($line)) {
			foreach ($line as $item) {
				$result->setLen(max($this->len, $item->len));
				for ($i=0, $l=min($result->_innerLen, $item->_innerLen); $i<$l; $i++) {
					$result->_map[$i] = $result->_map[$i] | $item->_map[$i];
				}
			}
		} else {
			$result->setLen(max($this->len, $line->len));
			for ($i=0, $l=min($result->_innerLen, $line->_innerLen); $i<$l; $i++) {
				$result->_map[$i] = $result->_map[$i] | $line->_map[$i];
			}
		}
		return $result;
	}

	public function findSpace($size) {
		$start = null;
		$sum = 0;
		for ($i=0, $l=$this->_len; $i<$l; $i++) {
			$val = $this->getBit($i);
			if ($val) {
				$start = null;
				continue;
			}

			if ($start === null) {
				$start = $i;
				$sum = 1;
			} else $sum++;

			if ($sum == $size) return $start;
		}

		return false;
	}

	public function toString() {
		$result = '';
		for ($i=0, $l=$this->_len; $i<$l; $i++) $result .= $this->getBit($i);
		return $result;
	}
}
