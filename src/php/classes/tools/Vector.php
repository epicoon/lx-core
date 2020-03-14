<?php

namespace lx;

/**
 * Class Vector
 * @package lx
 */
class Vector implements ArrayInterface
{
	use ArrayTrait;

	/**
	 * Vector constructor.
	 * @param mixed ...$args
	 */
	public function __construct(...$args)
	{
		$count = count($args);
		$arr = [];
		if ($count == 1) {
			if (is_array($args[0])) {
				$arr = array_values($args[0]);
			} else {
				$arr = [$args];
			}
		} elseif ($count > 1) {
			$arr = $args;
		}

		$this->constructArray($arr);
	}

	/**
	 * @param int $len
	 * @return Vector
	 */
	public static function create($len = 0)
	{
		return new self(array_fill(0, $len, 0));
	}

	/**
	 * @param mixed $el
	 * @return $this
	 */
	public function push($el)
	{
		$this->arrayValue[] = $el;
		return $this;
	}

	/**
	 * @param mixed $el
	 */
	public function pushUnique($el)
	{
		if (!$this->contains($el)) {
			$this->push($el);
		}
	}

	/**
	 * @param ArrayInterface|array $elems
	 * @return Vector
	 */
	public function merge($elems)
	{
		if ($elems instanceof ArrayInterface) {
			return $this->insert($this->len(), $elems->toArray());
		}

		if (is_array($elems)) {
			return $this->insert($this->len(), $elems);
		}
	}

	/**
	 * @param mixed $el
	 * @return bool
	 */
	public function remove($el)
	{
		$index = $this->getKeyByValue($el);
		if ($index === false) return false;
		$this->splice($index);
		return true;
	}

	/**
	 * @param string $str
	 * @return string
	 */
	public function join($str)
	{
		return implode($str, $this->arrayValue);
	}

	/**
	 * @param int $index
	 * @param int $count
	 * @param ArrayInterface|array $replacement
	 * @return $this
	 */
	public function splice($index, $count = 1, $replacement = [])
	{
		if ($replacement instanceof ArrayInterface) {
			$replacement = $replacement->toArray();
		}

		if (!is_array($replacement)) {
			$replacement = [$replacement];
		}

		array_splice($this->arrayValue, $index, $count, $replacement);
		return $this;
	}

	/**
	 * @param int $index
	 * @param ArrayInterface|array $elems
	 * @return $this
	 */
	public function insert($index, $elems)
	{
		return $this->splice($index, 0, $elems);
	}

	/**
	 * @param callable $func
	 */
	public function each($func)
	{
		foreach ($this->arrayValue as $i => $item) {
			$func($item, $i, $this);
		}
	}

	/**
	 * @param callable $func
	 */
	public function eachRevert($func)
	{
		for ($i = $this->len() - 1; $i >= 0; $i--) {
			$func($this->arrayValue[$i], $i, $this);
		}
	}

	/**
	 * @param int $i0
	 * @param int $i1
	 * @return mixed
	 */
	public function maxOnRange($i0 = 0, $i1 = null)
	{
		if ($i1 === null || $i1 >= $this->len()) {
			$i1 = $this->len() - 1;
		}

		$max = $this->arrayValue[$i0];
		for ($i = $i0 + 1; $i <= $i1; $i++) {
			if ($this->arrayValue[$i] > $max) $max = $this->arrayValue[$i];
		}

		return $max;
	}
}
