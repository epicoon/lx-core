<?php

namespace lx;

/**
 * Class Iterator
 * @package lx
 */
class Iterator implements \Iterator
{
	/** @var array */
	private $var = [];

	/**
	 * Iterator constructor.
	 * @param array $array
	 */
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

	/**
	 * @return mixed
	 */
	public function current()
	{
		return current($this->var);
	}

	/**
	 * @return mixed
	 */
	public function key()
	{
		return key($this->var);
	}

	/**
	 * @return mixed
	 */
	public function next()
	{
		return next($this->var);
	}

	/**
	 * @return bool
	 */
	public function valid()
	{
		$key = key($this->var);
		return ($key !== NULL && $key !== FALSE);
	}
}
