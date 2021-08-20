<?php

namespace lx;

/**
 * @see ArrayInterface
 */
trait ArrayTrait
{
	protected int $arrayNumericIndexCounter = 0;
	protected array $arrayValue = [];
	protected bool $arrayIsAssoc = true;

	public function __constructArray(iterable $array = [])
	{
		if (!is_array($array)) {
		    if (is_object($array) && method_exists($array, 'toArray')) {
		        $array = $array->toArray();
            } else {
		        $temp = [];
		        foreach ($array as $key => $value) {
		            $temp[$key] = $value;
                }
		        $array = $temp;
            }
		}
		
		if (empty($array)) {
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
				if ($key !== $currentKey) {
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

	public function getIndex(): int
	{
		return $this->arrayNumericIndexCounter;
	}

	public function isAssoc(): bool
	{
		return $this->arrayIsAssoc;
	}

	public function isEmpty(): bool
	{
		return empty($this->arrayValue);
	}

	public function count(): int
	{
		return count($this->arrayValue);
	}

	public function clear(): void
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

		return $this->arrayValue[$this->count() - 1];
	}

	/**
	 * @param mixed $value
	 * @return mixed
	 */
	public function getKeyByValue($value)
	{
	    $result = array_search($value, $this->arrayValue);
	    if ($result === false) {
	        return null;
        }
	    
	    return $result;
	}

    /**
     * @param mixed $value
     */
    public function removeValue($value)
    {
        $key = $this->getKeyByValue($value);
        if ($key === null) {
            return;
        }

        $this->offsetUnsetProcess($key);
        if (!$this->isAssoc()) {
            $this->arrayValue = array_values($this->arrayValue);
        }
    }

	/**
	 * @param mixed $value
	 */
	public function contains($value): bool
	{
		return $this->getKeyByValue($value) !== null;
	}

    public function merge(iterable $iterable): void
    {
        if (is_object($iterable) && method_exists($iterable, 'toArray')) {
            $iterable = $iterable->toArray();
        } elseif (!is_array($iterable)) {
            $temp = [];
            foreach ($iterable as $key => $value) {
                $temp[$key] = $value;
            }
            $iterable = $temp;
        }

        $this->arrayValue = array_merge($this->arrayValue, $iterable);
    }

	public function toArray(): array
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

		if ($this->beforeSet($offset, $value) === false) {
		    return;
        }
		$this->offsetSetProcess($offset, $value);
        $this->afterSet($offset, $value);
	}

    /**
     * @param mixed $offset
     * @param mixed $value
     */
	protected function offsetSetProcess($offset, $value)
    {
        $this->arrayValue[$offset] = $value;
        if (is_string($offset)) {
            $this->arrayIsAssoc = true;
        }
    }

    /**
     * @param mixed $key
     * @param mixed $value
     */
	protected function beforeSet($key, $value): bool
    {
        return true;
    }

    /**
     * @param mixed $key
     * @param mixed $value
     */
    protected function afterSet($key, $value)
    {
        // pass
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
	    if (!array_key_exists($offset, $this->arrayValue)) {
	        return;
        }
	    
	    $value = $this->arrayValue[$offset];
	    if ($this->beforeUnset($offset, $value) === false) {
	        return;
        }
        $this->offsetUnsetProcess($offset);
	    $this->afterUnset($offset, $value);
	    
		//TODO - проверка на ансет из середины перечислимого массива, чтобы сменить его на ассоциативный
	}

    /**
     * @param mixed $offset
     */
	protected function offsetUnsetProcess($offset)
    {
        unset($this->arrayValue[$offset]);
    }

    /**
     * @param mixed $key
     * @param mixed $value
     */
    protected function beforeUnset($key, $value): bool
    {
        return true;
    }

    /**
     * @param mixed $key
     * @param mixed $value
     */
    protected function afterUnset($key, $value)
    {
        // pass
    }

	/**
	 * @return Iterator
	 */
	public function getIterator()
	{
		return new Iterator($this->arrayValue);
	}
}
