<?php

namespace lx;

/**
 * Class DataObject
 * @package lx
 */
class DataObject
{
	/** @var null */
	private static $nullCache;
	
	/** @var array */
	protected $_prop = [];

	/**
	 * @param array $arr
	 * @return DataObject
	 */
	public static function create($arr = [])
	{
		if ($arr instanceof DataObject) return $arr;

		$obj = new static();
		if (is_array($arr)) $obj->setProperties($arr);
		return $obj;
	}

	/**
	 * @param string $prop
	 * @param mixed $val
	 */
	public function __set($prop, $val)
	{
		$this->setProperty($prop, $val);
	}

	/**
	 * @param string $prop
	 * @return mixed
	 */
	public function &__get($prop)
	{
	    return $this->getProperty($prop);
	}

	/**
	 * Returns the dynamic properties
	 * 
	 * @return array
	 */
	public function getProperties()
	{
		return $this->_prop;
	}

	/**
	 * @param array $arr
	 */
	public function setProperties($arr)
	{
		$this->_prop = $arr;
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function isNull($name)
	{
		return (array_key_exists($name, $this->_prop) && $this->_prop[$name] === null);
	}

    /**
     * @param string $prop
     * @param mixed $val
     */
    public function setProperty($prop, $val)
    {
        $this->_prop[$prop] = $val;
    }

    /**
     * @param string $name
     * @return mixed|null
     */
	public function &getProperty($name)
    {
        if (array_key_exists($name, $this->_prop)) {
            return $this->_prop[$name];
        }

        return $this->null();
    }

    /**
     * @param string $name
     */
	public function dropProperty($name)
    {
        unset($this->_prop[$name]);
    }

	/**
	 * Returns a dynamic property and deletes it from the object
	 * 
	 * @param string $name
	 * @return mixed|null
	 */
	public function extract($name)
	{
		if (!array_key_exists($name, $this->_prop)) return null;
		$res = $this->_prop[$name];
		unset($this->_prop[$name]);
		return $res;
	}

	/**
	 * Object is empty if it doesn't have dynamic properties
	 * 
	 * @return bool
	 */
	public function isEmpty()
	{
		return count($this->_prop) === 0;
	}

	/**
	 * Returns first defined property (dynamic or static)
	 * 
	 * @param string $names
	 * @param mixed $default
	 * @return mixed
	 */
	public function getFirstDefined($names, $default=null)
	{
		if (is_string($names)) $names = [$names];
		foreach ($names as $name) {
			if (array_key_exists($name, $this->_prop)) return $this->_prop[$name];
			if (property_exists($this, $name)) return $this->$name;
		}
		return $default;
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function hasOwnProperty($name)
	{
		return property_exists($this, $name);
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function hasDynamicProperty($name)
	{
		return array_key_exists($name, $this->_prop);
	}

	/**
	 * Check the property exists (dynamic or static)
	 *
	 * @param string $name
	 * @return bool
	 */
	public function hasProperty($name)
	{
		return (
			property_exists($this, $name)
			||
			array_key_exists($name, $this->_prop)
		);
	}

	/**
	 * @param string $name
	 * @param mixed $val
	 * @return bool
	 */
	public function testProperty($name, $val)
	{
		if (!$this->hasProperty($name)) return false;
		return $this->$name == $val;
	}

	/**
	 * @param string $name
	 */
	public function hasMethod($name)
	{
		require method_exists($this, $name);
	}

	/**
	 * @return null
	 */
	private function & null()
	{
		self::$nullCache = null;
		return self::$nullCache;
	}
}
