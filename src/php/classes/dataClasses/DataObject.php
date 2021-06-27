<?php

namespace lx;

class DataObject implements ArrayInterface
{
    use ArrayTrait;
    
	/** @var null */
	private static $nullCache;

	protected array $methods = [];

	public static function create(iterable $arr = []): DataObject
	{
		if ($arr instanceof DataObject) return $arr;
		return new static($arr);
	}
	
	public function __construct(iterable $arr = [])
    {
        $this->__constructArray($arr);
    }

	/**
	 * @param mixed $val
	 */
	public function __set(string $prop, $val): void
	{
	    if (is_callable($val)) {
	        $this->methods[$prop] = $val->bindTo($this);
	        return;
        }
	    
		$this->setProperty($prop, $val);
	}

	/**
	 * @return mixed
	 */
	public function &__get(string $prop)
	{
	    return $this->getProperty($prop);
	}

    /**
     * @return mixed
     */
	public function __call(string $name, array $arguments = [])
    {
        if (!$this->hasMethod($name)) {
            return null;
        }
        
        $method = $this->methods[$name];
        
        if (empty($arguments)) {
            return call_user_func($method);
        } else {
            return call_user_func_array($method, $arguments);
        }
    }

	public function getProperties(): array
	{
        return $this->arrayValue;
	}

	public function setProperties(array $arr)
	{
		$this->arrayValue = $arr;
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function isNull($name)
	{
		return (array_key_exists($name, $this->arrayValue) && $this->arrayValue[$name] === null);
	}

    /**
     * @param mixed $val
     */
    public function setProperty(string $prop, $val)
    {
        $this->arrayValue[$prop] = $val;
    }

    /**
     * @return mixed
     */
	public function &getProperty(string $name)
    {
        if (array_key_exists($name, $this->arrayValue)) {
            return $this->arrayValue[$name];
        }

        self::$nullCache = null;
        return self::$nullCache;
    }

	public function dropProperty(string $name): void
    {
        unset($this->arrayValue[$name]);
    }

	/**
	 * @return mixed
	 */
	public function extract(string $name)
	{
        if (!array_key_exists($name, $this->arrayValue)) {
            return null;
        }

        $res = $this->arrayValue[$name];
        unset($this->arrayValue[$name]);
		return $res;
	}


	/**
	 * @param array|string $names
	 * @param mixed $default
	 * @return mixed
	 */
	public function getFirstDefined($names, $default = null)
	{
		if (is_string($names)) {
		    $names = [$names];
        }

		foreach ($names as $name) {
			if (array_key_exists($name, $this->arrayValue)) {
			    return $this->arrayValue[$name];
            }
		}

		return $default;
	}

	/**
	 * @param mixed $val
	 */
	public function testProperty(string $name, $val, bool $strickt = false): bool
	{
		if (!$this->contains($name)) {
		    return false;
        }

		if ($strickt) {
            return $this->$name === $val;
        } else {
            return $this->$name == $val;
        }
	}

	public function hasMethod(string $name): bool
	{
	    return array_key_exists($name, $this->methods);
	}
}
