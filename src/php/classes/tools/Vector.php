<?php

namespace lx;

class Vector implements ArrayInterface
{
	use ArrayTrait;

	/**
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

		$this->__constructArray($arr);
	}

	public static function create(int $len = 0): Vector
	{
		return new self(array_fill(0, $len, 0));
	}

	/**
	 * @param mixed $el
	 */
	public function push($el): void
	{
		$this->arrayValue[] = $el;
	}

	/**
	 * @param mixed $el
	 */
	public function pushUnique($el): bool
	{
        if ($this->contains($el)) {
            return false;
        }
	    
        $this->push($el);
        return true;
	}

	public function merge(iterable $elems): void
	{
        $this->insert($this->len(), $elems);
	}

	/**
	 * @param mixed $el
	 */
	public function remove($el): bool
	{
		$index = $this->getKeyByValue($el);
		if ($index === null) return false;
		$this->splice($index);
		return true;
	}

	public function join(string $str): string
	{
		return implode($str, $this->arrayValue);
	}

	public function splice(int $index, int $count = 1, iterable $replacement = []): void
	{
	    if (!empty($replacement)) {
            if (is_object($replacement) && method_exists($replacement, 'toArray')) {
                $replacement = array_values($replacement->toArray());
            } elseif (is_array($replacement)) {
                $replacement = array_values($replacement);
            } else {
                $temp = [];
                foreach ($replacement as $elem) {
                    $temp[] = $elem;
                }
                $replacement = $temp;
            }
        }
	    
		array_splice($this->arrayValue, $index, $count, $replacement);
	}

	public function insert(int $index, iterable $elems): void
	{
		$this->splice($index, 0, $elems);
	}

	public function each(callable $func): void
	{
		foreach ($this->arrayValue as $i => $item) {
			$func($item, $i, $this);
		}
	}

	public function eachRevert(callable $func): void
	{
		for ($i = $this->len() - 1; $i >= 0; $i--) {
			$func($this->arrayValue[$i], $i, $this);
		}
	}

	/**
	 * @return mixed
	 */
	public function maxOnRange(int $i0 = 0, ?int $i1 = null)
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
