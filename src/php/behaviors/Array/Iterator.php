<?php

namespace lx;

class Iterator implements \Iterator
{
	private array $var = [];

	public function __construct(array $array)
	{
        $this->var = $array;
	}

    /**
     * @return void
     */
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
