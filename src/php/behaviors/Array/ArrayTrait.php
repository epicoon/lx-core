<?php

namespace lx;

/**
 * Trait ArrayTrait
 * @package lx
 */
trait ArrayTrait
{
	/** @var int */
	protected $arrayNumericIndexCounter;

	/** @var array */
	protected $arrayValue;

	/** @var bool */
	protected $arrayIsAssoc;

	/**
	 * @magic __construct
	 * @param array|mixed $array
	 */
	public function constructArray($array)
	{
		if ( ! is_array($array)) {
			$this->arrayValue = [$array];
			$this->arrayNumericIndexCounter = 1;
			$this->arrayIsAssoc = false;
		} elseif (empty($array)) {
			$this->arrayValue = [];
			$this->arrayNumericIndexCounter = 0;
			$this->arrayIsAssoc = false;
		} else {
			$this->arrayValue = $array;
			$maxIndex = -INF;
			$match = false;
			$currentKey = 0;
			$isAssoc = false;
			foreach ($array as $key => $value) {
				if ($key != $currentKey) {
					$isAssoc = true;
				}
				$currentKey++;

				if (is_numeric($key) && $key > $maxIndex) {
					$match = true;
					$maxIndex = $key;
				}
			}

			$this->arrayNumericIndexCounter = $match ? ($maxIndex + 1) : 0;
			$this->arrayIsAssoc = $isAssoc;
		}
	}

	/**
	 * @return int
	 */
	public function getIndex()
	{
		return $this->arrayNumericIndexCounter;
	}

	/**
	 * @return bool
	 */
	public function isAssoc()
	{
		return $this->arrayIsAssoc;
	}

	/**
	 * @return bool
	 */
	public function isEmpty()
	{
		return empty($this->arrayValue);
	}

	/**
	 * @return int
	 */
	public function len()
	{
		return count($this->arrayValue);
	}

	public function clear()
	{
		$this->arrayValue = [];
		$this->arrayNumericIndexCounter = 0;
		$this->arrayIsAssoc = false;
	}

	/**
	 * @return mixed
	 */
	public function pop()
	{
		if ($this->isEmpty()) {
			return null;
		}
		return array_pop($this->arrayValue);
	}

	/**
	 * @return mixed
	 */
	public function shift()
	{
		if ($this->isEmpty()) {
			return null;
		}
		return array_shift($this->arrayValue);
	}

	/**
	 * @return mixed
	 */
	public function getFirst()
	{
		if ($this->isEmpty()) {
			return null;
		}

		if ($this->isAssoc()) {
			$keys = array_keys($this->arrayValue);
			return $this->arrayValue[$keys[0]];
		}

		return $this->arrayValue[0];
	}

	/**
	 * @return mixed
	 */
	public function getLast()
	{
		if ($this->isEmpty()) {
			return null;
		}

		if ($this->isAssoc()) {
			$keys = array_keys($this->arrayValue);
			return $this->arrayValue[$keys[count($keys) - 1]];
		}

		return $this->arrayValue[$this->len() - 1];
	}

	/**
	 * @param mixed $value
	 * @return mixed|false
	 */
	public function getKeyByValue($value)
	{
		return array_search($value, $this->arrayValue);
	}

	/**
	 * @param mixed $value
	 * @return bool
	 */
	public function contains($value)
	{
		return $this->getKeyByValue($elem) !== false;
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		return $this->arrayValue;
	}

	/**
	 * @param mixed $offset
	 * @return mixed
	 */
	public function offsetGet($offset)
	{
		if ( ! array_key_exists($offset, $this->arrayValue)) {
			return null;
		}

		return $this->arrayValue[$offset];
	}

	/**
	 * @param mixed $offset
	 * @param mixed $value
	 */
	public function offsetSet($offset, $value)
	{
		if ($offset === null) {
			$offset = $this->arrayNumericIndexCounter++;
		}

		$this->arrayValue[$offset] = $value;
		//TODO - числовое значение за пределами текущего размера может превратить перечислимы массив в ассоциативный
		if (is_string($offset)) {
			$this->arrayIsAssoc = true;
		}
	}

	/**
	 * @param mixed $offset
	 * @return bool
	 */
	public function offsetExists($offset)
	{
		return array_key_exists($offset, $this->arrayValue);
	}

	/**
	 * @param mixed $offset
	 */
	public function offsetUnset($offset)
	{
		unset($this->arrayValue[$offset]);
		//TODO - проверка на ансет из середины перечислимого массива, чтобы сменить его на ассоциативный
	}

	/**
	 * @return Iterator
	 */
	public function getIterator()
	{
		return new Iterator($this->arrayValue);
	}
}
