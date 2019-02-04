<?php

namespace lx;

/**
 *Класс - обычный массив (не ассоциативный), с методами на манер js массива
 *Слово Array уже занято
 *
 *	__construct($data=null)
 *	set($data)
 *	reset()
 * 	at($i)
 *	set($i, $value)
 * 	len()
 *	empty()
 *	first()
 *	last()
 *	push($el)
 *	merge($elems)
 *	remove($el)
 *	pop()
 *	shift()
 *	join($str)
 *	splice($index, $count=1, $replacement=[])
 *	insert($index, $elems)
 *	indexOf($elem)
 *	contain($elem)
 *	each($func)
 *	eachRevert($func)
 *	maxOnRange($i0, $i1)
 * */
class Vector extends DataObject {
	private $data = [];

	public function __construct($data=null) {
		if ($data !== null) $this->init($data);
	}

	public static function createLen($len) {
		$v = new self();
		$v->setData(array_fill(0, $len, 0));
		return $v;
	}

	public function __set($prop, $value) {
		switch ($prop) {
			case 'len':
			case 'isEmpty':
			return;
		}
		parent::__set($prop, $value);
	}

	public function &__get($prop) {
		switch ($prop) {
			case 'len'    : $res = count($this->data); return $res;
			case 'isEmpty': $res = empty($this->data); return $res;
		}
		return parent::__get($prop);
	}

	public function init($data) {
		//todo Collection

		if (!is_array($data)) $data = [$data];
		foreach ($data as $value)
			$this->data[] = $value;
	}

	public function getData() {
		return $this->data;
	}

	public function setData($arr) {
		$this->data = $arr;
	}

	public function reset() {
		$this->data = [];
		return $this;
	}

	public function at($i) {
		if ($i < 0 || $i >= $this->len) return null;
		return $this->data[$i];
	}

	public function set($i, $value) {
		$this->data[$i] = $value;
		return $this;
	}

	public function first() {
		return $this->at(0);
	}

	public function last() {
		return $this->at(count($this->data) - 1);
	}

	public function push($el) {
		$this->data[] = $el;
		return $this;
	}

	public function pushUnique($el) {
		if (!$this->contain($el))
			$this->push($el);
	}

	public function merge($elems) {
		if ($elems instanceof Vector) return $this->insert($this->len, $elems->getData());
		return $this->insert($this->len, $elems);
	}

	public function remove($el) {
		$index = $this->indexOf($el);
		if ($index == -1) return false;
		$this->splice($index);
		return true;
	}

	public function pop() {
		if ($this->isEmpty) return null;
		return array_pop($this->data);
	}

	public function shift() {
		return array_shift($this->data);
	}

	public function join($str) {
		return implode($str, $this->data);
	}

	public function splice($index, $count=1, $replacement=[]) {
		if ($replacement instanceof Vector) $replacement = $replacement->getData();
		if (!is_array($replacement)) $replacement = [$replacement];
		array_splice($this->data, $index, $count, $replacement);
		return $this;
	}

	public function insert($index, $elems) {
		return $this->splice($index, 0, $elems);
	}

	public function indexOf($elem) {
		$index = array_search($elem, $this->data);
		if ($index === false) return -1;
		return $index;
	}

	public function contain($elem) {
		return $this->indexOf($elem) !== -1;
	}

	public function each($func) {
		foreach ($this->data as $i => $item)
			$func($item, $i, $this);
	}

	public function eachRevert($func) {
		for ($i=$this->len-1; $i>=0; $i--)
			$func($this->data[$i], $i, $this);
	}

	/**
	 * Только для вектора чисел
	 * */
	public function maxOnRange($i0=0, $i1=null) {
		if ($i1 === null) $i1 = $this->len;
		$max = $this->data[$i0];
		for ($i=$i0+1; $i<=$i1; $i++)
			if ($this->data[$i] > $max) $max = $this->data[$i];
		return $max;
	}
}
