<?php

namespace lx;

class ClassHelper
{
	public static function checkInstance(string $currentClass, string $baseClass): bool
	{
		return (
            $currentClass == $baseClass
            || is_subclass_of($currentClass, $baseClass)
            || self::implements($currentClass, $baseClass)
        );
	}

	public static function exists(string $className): bool
	{
		$autoloader = Autoloader::getInstance();
		try {
			if (class_exists($className)) {
				return true;
			}
			return $autoloader->getClassPath($className) !== null;
		} catch (\Exception $e) {
			return $autoloader->getClassPath($className) !== null;
		}
	}

	/**
     * @param string|Object $class
	 * @param array|string $interfaces
	 */
	public static function implements($class, $interfaces): bool
	{
		if (!self::exists($class)) {
			return false;
		}

		try {
			$reflected = new \ReflectionClass($class);
            foreach (array($interfaces) as $interface) {
                if (!$reflected->implementsInterface($interface)) {
                    return false;
                }
            }
		} catch (\Exception $e) {
			return false;
		}

		return true;
	}

    /**
     * @param string|Object $class
     */
	public static function publicPropertyExists($class, string $property): bool
	{
		if ( ! property_exists($class, $property)) {
			return false;
		}

		$reflected = new \ReflectionClass($class);
		$reflProperty = $reflected->getProperty($property);
		return $reflProperty->isPublic();
	}

    /**
     * @param string|Object $class
     */
	public static function protectedPropertyExists($class, string $property): bool
	{
		if ( ! property_exists($class, $property)) {
			return false;
		}

		$reflected = new \ReflectionClass($class);
		$reflProperty = $reflected->getProperty($property);
		return $reflProperty->isProtected();
	}

    /**
     * @param string|Object $class
     */
	public static function privatePropertyExists($class, string $property): bool
	{
		if ( ! property_exists($class, $property)) {
			return false;
		}

		$reflected = new \ReflectionClass($class);
		$reflProperty = $reflected->getProperty($property);
		return $reflProperty->isPrivate();
	}

    /**
     * @param string|Object $class
     */
	public static function publicMethodExists($class, string $method): bool
	{
		if ( ! method_exists($class, $method)) {
			return false;
		}

		$reflected = new \ReflectionClass($class);
		$reflMethod = $reflected->getMethod($method);
		return $reflMethod->isPublic();
	}

    /**
     * @param string|Object $class
     */
	public static function protectedMethodExists($class, string $method): bool
	{
		if ( ! method_exists($class, $method)) {
			return false;
		}

		$reflected = new \ReflectionClass($class);
		$reflMethod = $reflected->getMethod($method);
		return $reflMethod->isProtected();
	}

    /**
     * @param string|Object $class
     */
	public static function privateMethodExists($class, string $method): bool
	{
		if ( ! method_exists($class, $method)) {
			return false;
		}

		$reflected = new \ReflectionClass($class);
		$reflMethod = $reflected->getMethod($method);
		return $reflMethod->isPrivate();
	}

	/**
	 * @param mixed $obj class or object
	 */
	public static function getNamespace($obj): string
	{
		$reflected = new \ReflectionClass($obj);
		if (!$reflected->inNamespace()) {
			return '';
		}

		return $reflected->getNamespaceName();
	}

	/**
	 * @return array [namespace, ownClassName]
	 */
	public static function splitClassName(string $className): array
	{
		preg_match_all('/(.*)[.\\\]([^\\' . '\.]+)$/', $className, $matches);
		return [$matches[1][0], $matches[2][0]];
	}

	public static function getTraitNames(string $className, bool $all = false): array
	{
		$re = new \ReflectionClass($className);
		if (!$all) {
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
	 */
	public static function prepareConfig($config, ?string $defaultClass = null, array $defaultParams = []): ?array
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
	 */
	public static function defineServiceName(string $className): ?string
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
	 */
	public static function defineService(string $className): ?Service
	{
		$serviceName = self::defineServiceName($className);
		if (!$serviceName) {
			return null;
		}

		return \lx::$app->getService($serviceName);
	}
}
