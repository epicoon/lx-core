<?php

namespace lx;

class ModelCollection implements \ArrayAccess, \IteratorAggregate
{
	private $list;
	private $numericIndexCounter;

	public function __construct($array = [])
	{
		if ( ! is_array($array) || $array == []) {
			$this->list = [];
			$this->numericIndexCounter = 0;
		} else {
			$this->list = $array;
			$maxIndex = -INF;
			$match = false;
			foreach ($array as $key => $value) {
				if (is_numeric($key) && $key > $maxIndex) {
					$match = true;
					$maxIndex = $key;
				}
			}

			$this->numericIndexCounter = $match ? $maxIndex : 0;
		}
	}

	public function toArray()
	{
		return $this->list;
	}

	public function isEmpty()
	{
		return empty($this->list);
	}

	public function merge($collection)
	{
		if (is_object($collection) && method_exists($collection, 'toArray')) {
			$collection = $collection->toArray();
		}

		if ( ! is_array($collection)) {
			return;
		}

		$this->list = array_merge($this->list, $collection);
		return $this;
	}

	public function getField($name)
	{
		$result = [];
		foreach ($this->list as $model) {
			$result[] = $model->$name;
		}

		return $result;
	}

	public function offsetGet($offset)
	{
		if ( ! array_key_exists($offset, $this->list)) {
			return null;
		}

		return $this->list[$offset];
	}

	public function offsetSet($offset, $value)
	{
		if ($offset === null) {
			$offset = $this->numericIndexCounter++;
		}

		$this->list[$offset] = $value;
	}

	public function offsetExists($offset)
	{
		return array_key_exists($offset, $this->list);
	}

	public function offsetUnset($offset)
	{
		unset($this->list[$offset]);
	}

	public function getIterator()
	{
		return new Iterator($this->list);
	}
}


class Iterator implements \Iterator
{
	private $var = [];

	public function __construct($array)
	{
		if (is_array($array)) {
			$this->var = $array;
		}
	}

	public function rewind()
	{
		reset($this->var);
	}

	public function current()
	{
		return current($this->var);
	}

	public function key()
	{
		return key($this->var);
	}

	public function next()
	{
		return next($this->var);
	}

	public function valid()
	{
		$key = key($this->var);
		return ($key !== NULL && $key !== FALSE);
	}
}
