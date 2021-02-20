<?php

namespace lx;

/**
 * Trait ArrayTrait
 * @package lx
 */
trait ArrayTrait
{
	/** @var int */
	protected $arrayNumericIndexCounter = 0;

	/** @var array */
	protected $arrayValue = [];

	/** @var bool */
	protected $arrayIsAssoc = true;

	public function __construct()
    {
        $this->constructArray([]);
    }

	/**
	 * @magic __construct
	 * @param iterable $array
	 */
	public function __constructArray($array = [])
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
     */
    public function removeValue($value)
    {
        $key = $this->getKeyByValue($value);
        if ($key === false) {
            return;
        }

        $this->offsetUnsetProcess($key);
        if (!$this->isAssoc()) {
            $this->arrayValue = array_values($this->arrayValue);
        }
    }

	/**
	 * @param mixed $value
	 * @return bool
	 */
	public function contains($value)
	{
		return $this->getKeyByValue($value) !== false;
	}

    /**
     * @param iterable $iterable
     * @return iterable
     */
    public function merge($iterable)
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
        return $this;
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
	protected function beforeSet($key, $value)
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
    protected function beforeUnset($key, $value)
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
