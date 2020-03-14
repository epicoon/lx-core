<?php

namespace lx;

/**
 * Class ClassHelper
 * @package lx
 */
class ClassHelper
{
	/**
	 * @param string $currentClass
	 * @param string $baseClass
	 * @return bool
	 */
	public static function checkInstance($currentClass, $baseClass)
	{
		return ($currentClass == $baseClass || is_subclass_of($currentClass, $baseClass));
	}

	/**
	 * @param string $className
	 * @return bool
	 */
	public static function exists($className)
	{
		$autoloader = Autoloader::getInstance();
		try {
			if (class_exists($className)) {
				return true;
			}
			return $autoloader->getClassPath($className) !== false;
		} catch (\Exception $e) {
			return $autoloader->getClassPath($className) !== false;
		}
	}

	/**
	 * @param string $className
	 * @param array|string $interfaces
	 * @return bool
	 */
	public function implements($className, $interfaces)
	{
		if (!self::exists($className)) {
			return false;
		}

		try {
			$reflected = new \ReflectionClass($className);
		} catch (\Exception $e) {
			return false;
		}

		foreach (array($interfaces) as $interface) {
			if (!$reflected->implementsInterface($interface)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @param string $class
	 * @param string $property
	 * @return bool
	 */
	public static function publicPropertyExists($class, $property)
	{
		if ( ! property_exists($class, $property)) {
			return false;
		}

		$reflected = new \ReflectionClass($class);
		$reflProperty = $reflected->getProperty($property);
		return $reflProperty->isPublic();
	}

	/**
	 * @param string $class
	 * @param string $property
	 * @return bool
	 */
	public static function protectedPropertyExists($class, $property)
	{
		if ( ! property_exists($class, $property)) {
			return false;
		}

		$reflected = new \ReflectionClass($class);
		$reflProperty = $reflected->getProperty($property);
		return $reflProperty->isProtected();
	}

	/**
	 * @param string $class
	 * @param string $property
	 * @return bool
	 */
	public static function privatePropertyExists($class, $property)
	{
		if ( ! property_exists($class, $property)) {
			return false;
		}

		$reflected = new \ReflectionClass($class);
		$reflProperty = $reflected->getProperty($property);
		return $reflProperty->isPrivate();
	}

	/**
	 * @param string $class
	 * @param string $method
	 * @return bool
	 */
	public static function publicMethodExists($class, $method)
	{
		if ( ! method_exists($class, $method)) {
			return false;
		}

		$reflected = new \ReflectionClass($class);
		$reflMethod = $reflected->getMethod($method);
		return $reflMethod->isPublic();
	}

	/**
	 * @param string $class
	 * @param string $method
	 * @return bool
	 */
	public static function protectedMethodExists($class, $method)
	{
		if ( ! method_exists($class, $method)) {
			return false;
		}

		$reflected = new \ReflectionClass($class);
		$reflMethod = $reflected->getMethod($method);
		return $reflMethod->isProtected();
	}

	/**
	 * @param string $class
	 * @param string $method
	 * @return bool
	 */
	public static function privateMethodExists($class, $method)
	{
		if ( ! method_exists($class, $method)) {
			return false;
		}

		$reflected = new \ReflectionClass($class);
		$reflMethod = $reflected->getMethod($method);
		return $reflMethod->isPrivate();
	}

	/**
	 * Method returns namespace for class or object
	 *
	 * @param mixed $obj
	 * @return string
	 */
	public static function getNamespace($obj)
	{
		$reflected = new \ReflectionClass($obj);
		if (!$reflected->inNamespace()) {
			return '';
		}

		return $reflected->getNamespaceName();
	}

	/**
	 * Method splits name of the class for namespace and class own name
	 *
	 * @param string $className
	 * @return array
	 */
	public static function splitClassName($className)
	{
		preg_match_all('/(.*)[.\\\]([^\\' . '\.]+)$/', $className, $matches);
		return [$matches[1][0], $matches[2][0]];
	}

	/**
	 * @param string $className
	 * @param bool $all
	 * @return array
	 */
	public static function getTraitNames($className, $all = false)
	{
		$re = new \ReflectionClass($className);
		if ( ! $all) {
			return $re->getTraitNames();
		}

		$traitNames = [];
		$recursiveClasses = function ($re, &$traitNames) use (&$recursiveClasses) {
			$traitNames = array_merge($traitNames, $re->getTraitNames());
			if ($re->getParentClass() != false) {
				$recursiveClasses($re->getParentClass(), $traitNames);
			}
		};
		$recursiveClasses($re, $traitNames);
		return $traitNames;
	}

	/**
	 * Define class name and parameters for instance creation from basic configuration
	 * Configuration variants:
	 * 1. 'className'
	 * 2. [ 'class' => 'className', 'params' => [...] ]
	 * 3. [...] parameters array only if $defaultClass defined
	 *
	 * @param string|array $config
	 * @param string $defaultClass
	 * @param array $defaultParams
	 * @return array
	 */
	public static function prepareConfig($config, $defaultClass = null, $defaultParams = [])
	{
		$class = $defaultClass;
		$params = $defaultParams;

		if (is_string($config)) {
			$class = $config;
		} elseif (is_array($config)) {
			if (isset($config['class'])) {
				$class = $config['class'];
				unset($config['class']);
			}

			if (isset($config['params'])) {
				$params = $config['params'];
			} elseif ( ! empty($config)) {
				$params = $config;
			}
		}

		if ($class === null || !self::exists($class)) {
			return null;
		}

		return [
			'class' => $class,
			'params' => $params,
		];
	}

	/**
	 * Method defines service name to which the class belongs
	 *
	 * @param string $className
	 * @return string|null
	 */
	public static function defineServiceName($className)
	{
		$reflected = new \ReflectionClass($className);
		if (!$reflected->inNamespace()) {
			return null;
		}

		$classMap = Autoloader::getInstance()->map;

		$namespace = $reflected->getNamespaceName() . '\\';
		foreach ($classMap->namespaces as $key => $value) {
			if (preg_match('/^' . addcslashes($key, '\\') . '/', $namespace)) {
				return $value['package'];
			}
		}

		if (array_key_exists($className, $classMap->classes)) {
			return $classMap->classes[$className]['package'];
		}

		return null;
	}

	/**
	 * Method defines service to which the class belongs
	 * 
	 * @param string $className
	 * @return Service|null
	 */
	public static function defineService($className)
	{
		$serviceName = self::defineServiceName($className);
		if (!$serviceName) {
			return null;
		}

		return \lx::$app->getService($serviceName);
	}
}
