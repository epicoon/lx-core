<?php

namespace lx;

/**
 * Class DependencyProcessor
 * @package lx
 */
class DependencyProcessor extends Object implements FusionComponentInterface
{
	use ApplicationToolTrait;
	use FusionComponentTrait;

	protected $interfaces = [];
	protected $classes = [];

	/**
	 * @param string $className
	 * @param array $params
	 * @param array $dependencies
	 * @return mixed
	 */
	public function create($className, $params = [], $dependencies = [])
	{
		$re = new \ReflectionClass($className);
		if ($re->isSubclassOf(Object::class)) {
			return $this->createObject($re, $params, $dependencies);
		}

		return $this->createProcess($re, $params, $dependencies);
	}

	/**
	 * @param \ReflectionClass $re
	 * @param array $params
	 * @param array $dependencies
	 * @return mixed
	 */
	private function createProcess($re, $params, $dependencies)
	{
		if ( ! $re->hasMethod('__construct')) {
			return $re->newInstance();
		}

		$constructor = $re->getMethod('__construct');

		if ( ! $constructor->isPublic()) {
			return null;
		}

		$parameters = $constructor->getParameters();
		if (empty($parameters)) {
			return $re->newInstance();
		}

		if (empty($params)) {
			$paramsAreCountable = false;
		} else {
			$paramsAreCountable = !is_string(array_keys($params)[0]);
		}

		$name = $re->getName();
		$finalParams = [];
		foreach ($parameters as $i => $parameter) {
			if ($paramsAreCountable) {
				if (array_key_exists($i, $params)) {
					$finalParams[$i] = $params[$i];
					continue;
				}
			} else {
				$name = $parameter->getName();
				if (array_key_exists($name, $params)) {
					$finalParams[$i] = $params[$name];
					continue;
				}
			}

			$type = $parameter->getType();
			if ($type === null) {
				$finalParams[$i] = $parameter->isDefaultValueAvailable()
					? $parameter->getDefaultValue()
					: null;
				continue;
			}

			$typeName = $type->getName();
			$typeRe = new \ReflectionClass($typeName);
			if ($typeRe->isInterface()) {
				if (array_key_exists($typeName, $dependencies)) {
					$instance = $this->create($dependencies[$typeName]);
				} else {
					$instance = $this->createInstanceByInterface($typeName, $name);
				}
			} else {
				$instance = $this->create($typeName);
			}

			$finalParams[$i] = $instance;
		}

		$object = $re->newInstanceArgs($finalParams);
		return $object;
	}

	/**
	 * @param \ReflectionClass $re
	 * @param array $params
	 * @param array $dependencies
	 * @return mixed
	 */
	private function createObject(\ReflectionClass $reflection, $params, $dependencies)
	{
		$config = $params;
		$name = $reflection->getName();
		$protocol = $reflection->getMethod('getConfigProtocol')->invoke(null);

		foreach ($protocol as $paramName => $paramDiscr) {
			if (array_key_exists($paramName, $config)) {
				continue;
			}

			$instanceName = is_string($paramDiscr)
				? $paramDiscr
				: ($paramDiscr['instance'] ?? null);
			if ( ! $instanceName) {
				continue;
			}

			$paramRe = new \ReflectionClass($instanceName);
			if ($paramRe->isInterface()) {
				if (array_key_exists($instanceName, $dependencies)) {
					$instance = $this->create($dependencies[$instanceName]);
				} else {
					$instance = $this->createInstanceByInterface($instanceName, $name);
				}
			} else {
				$instance = $this->create($instanceName);
			}

			if ( ! $instance) {
				continue;
			}

			$config[$paramName] = $instance;
		}

		return $reflection->newInstance($config);
	}

	/**
	 * @param string $interfaceName
	 * @param string|null $className
	 * @return mixed|null
	 */
	private function createInstanceByInterface($interfaceName, $className = null)
	{
		$classForInterface = null;
		if ($className !== null && array_key_exists($className, $this->classes)) {
			$classData = $this->classes[$className];
			if (array_key_exists($interfaceName, $classData)) {
				$classForInterface = $classData[$interfaceName];
			}
		}

		if ( ! $classForInterface) {
			if (array_key_exists($interfaceName, $this->interfaces)) {
				$classForInterface = $this->interfaces[$interfaceName];
			}
		}

		if ( ! $classForInterface) {
			return null;
		}

		return $this->create($classForInterface);
	}
}
