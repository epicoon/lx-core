<?php

namespace lx;

/**
 * Class DevHelper
 * @package lx
 */
class DevHelper
{
	/**
	 * Method returns object property ignoring access type
	 * Use it only for test-like tasks while developing!
	 *
	 * @param mixed $object
	 * @param string $propertyName
	 * @return mixed
	 */
	public static function get($object, $propertyName)
	{
		$rc = new \ReflectionClass($object);
		if (!$rc->hasProperty($propertyName)) {
			return null;
		}

		$property = $rc->getProperty($propertyName);
		if ($property->isPublic()) {
			return $property->getValue($object);
		}

		$property->setAccessible(true);
		$result = $property->getValue($object);
		$property->setAccessible(false);
		return $result;
	}

	/**
	 * Method calls object methos ignoring access type
	 * Use it only for test-like tasks while developing!
	 *
	 * @param mixed $object
	 * @param string $methodName
	 * @param array $args
	 * @return mixed
	 */
	public static function call($object, $methodName, $args = [])
	{
		$rc = new \ReflectionClass($object);
		$method = $rc->getMethod($methodName);

		$isPrivate = $method->isPrivate() || $method->isProtected();
		if ($isPrivate) {
			$method->setAccessible(true);
		}
		if (is_object($object)) {
			$result = $method->invokeArgs($object, (array)$args);
		} else {
			$result = $method->invokeArgs(null, (array)$args);
		}
		if ($isPrivate) {
			$method->setAccessible(false);
		}

		return $result;
	}
}
