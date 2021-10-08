<?php

namespace lx;

use ReflectionClass;

class DependencyProcessor
{
	private array $interfaces = [];
	private array $classes = [];
	private array $addedServices = [];
	private array $instances = [];

	public function __construct(array $config = [])
	{
	    $this->addMap($config);
	    $this->setDefaults([
            DataFileInterface::class => DataFile::class,
            EventManagerInterface::class => EventManager::class,
            ResponseInterface::class => Response::class,
            HtmlRendererInterface::class => HtmlRenderer::class,
            HtmlTemplateProviderInterface::class => HtmlTemplateProvider::class,
            UserInterface::class => User::class,
        ]);
	}
	
	public function addMap(array $map, bool $rewrite = false): void
    {
        $interfaces = $map['interfaces'] ?? [];
        if (empty($this->interfaces)) {
            $this->interfaces = $interfaces;
        } else {
            $this->interfaces = ArrayHelper::mergeRecursiveDistinct($this->interfaces, $interfaces, $rewrite);
        }
        
        $classes = $map['classes'] ?? [];
        if (empty($this->classes)) {
            $this->classes = $classes;
        } else {
            $this->classes = ArrayHelper::mergeRecursiveDistinct($this->classes, $classes, $rewrite);
        }
    }

    /**
     * Validation creates dev-log messages if classes don't due to interfaces
     */
    public function validate(): void
    {
        foreach ($this->interfaces as $interface => $class) {
            $re = new \ReflectionClass($class);
            if (!$re->implementsInterface($interface)) {
                \lx::devLog([
                    'msg' => 'DI-processor notification! Configuration is wrong: class doesn\'t due to interface',
                    'interface' => $interface,
                    'class' => $class,
                ]);
            }
        }

        foreach ($this->classes as $mainClass => $data) {
            foreach ($data as $interface => $class) {
                $re = new \ReflectionClass($class);
                if (!$re->implementsInterface($interface)) {
                    \lx::devLog([
                        'msg' => 'DI-processor notification! Configuration is wrong: class doesn\'t due to interface',
                        'context' => $mainClass,
                        'interface' => $interface,
                        'class' => $class,
                    ]);
                }
            }
        }
    }

	/**
	 * @return mixed
	 */
	public function create(
	    string $classOrInterface,
        array $params = [],
        array $dependencies = [],
        ?string $contextClass = null
    ) {
		$re = new \ReflectionClass($classOrInterface);
		
		if ($re->isInterface()) {
		    return $this->createByInterface($classOrInterface, $params, $dependencies, null, $contextClass);
        }

		$isSingleton = false;
		if ($re->hasMethod('isSingleton')) {
		    $method = $re->getMethod('isSingleton');
            $isSingleton = $method->invoke(null);
		    if ($isSingleton && array_key_exists($classOrInterface, $this->instances)) {
		        return $this->instances[$classOrInterface];
            }
        }

		$instance = ($re->hasMethod('isLxObject'))
            ? $this->createObject($re, $params, $dependencies)
            : $this->createProcess($re, $params, $dependencies);

		if ($isSingleton) {
		    $this->instances[$classOrInterface] = $instance;
        }

		return $instance;
	}

	/**
	 * @return mixed
	 */
	public function createByInterface(
	    ?string $interface,
        array $params = [],
        array $dependencies = [],
        ?string $defaultClass = null,
        ?string $contextClass = null
    ) {
	    if ($interface === null) {
	        $classForInterface = $defaultClass;
        } else {
            $classForInterface = $this->findClassForInterface($interface, $contextClass);
            if (!$classForInterface) {
                $service = ClassHelper::defineService($interface);
                if (!$service || in_array($service->name, $this->addedServices)) {
                    $classForInterface = $defaultClass;
                } else {
                    $map = $service->getConfig('diProcessor') ?? [];
                    $interfaces = $map['interfaces'] ?? [];
                    $classes = $map['classes'] ?? [];
                    $this->interfaces = ArrayHelper::mergeRecursiveDistinct($this->interfaces, $interfaces);
                    $this->classes = ArrayHelper::mergeRecursiveDistinct($this->classes, $classes);
                    $this->addedServices[] = $service->name;
                    $classForInterface = $this->findClassForInterface($interface, $contextClass);
                    if (!$classForInterface) {
                        $classForInterface = $defaultClass;
                    }
                }
            }
        }

        if (!$classForInterface) {
            return null;
        }

        return $this->create($classForInterface, $params, $dependencies);
	}


	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * PRIVATE
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/**
	 * @return mixed
	 */
	private function createProcess(ReflectionClass $re, array $params, array $dependencies)
	{
		if (!$re->hasMethod('__construct')) {
			return $re->newInstance();
		}

		$constructor = $re->getMethod('__construct');

		if (!$constructor->isPublic()) {
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
			} elseif ($this->typeIsPrimitive($type->getName())) {
                $default = $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : new Undefined();
                if ($default instanceof Undefined) {
                    throw new \Exception("DI-processor can't create an instance of the $name"
                        .": typed parameter {$parameter->getName()} has to have default value");
                }
			    
			    $finalParams[$i] = $default;
                continue;
            }

			$typeName = $type->getName();
			
			$typeRe = new \ReflectionClass($typeName);
			if ($typeRe->isInterface()) {
				if (array_key_exists($typeName, $dependencies)) {
					$instance = $this->create($dependencies[$typeName]);
				} else {
					$instance = $this->createByInterface($typeName, [], [], null, $name);
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
	 * @return mixed
	 */
	private function createObject(ReflectionClass $reflection, array $params, array $dependencies)
	{
		$config = $params;
		$name = $reflection->getName();
		$protocol = $reflection->getMethod('getDependenciesConfig')->invoke(null);
		$diMap = $reflection->getMethod('getDependenciesDefaultMap')->invoke(null);

		foreach ($protocol as $paramName => $paramDescription) {
			if (array_key_exists($paramName, $config)) {
				continue;
			}
            
            if (is_string($paramDescription)) {
                $paramDescription = ['instance' => $paramDescription];
            }
            
            if ($paramDescription['lasy'] ?? false) {
                continue;
            }

			$instanceName = $paramDescription['instance'] ?? null;
			if (!$instanceName) {
				continue;
			}

			$paramRe = new \ReflectionClass($instanceName);
			if ($paramRe->isInterface()) {
				if (array_key_exists($instanceName, $dependencies)) {
					$instance = $this->create($dependencies[$instanceName]);
				} elseif (array_key_exists($paramName, $dependencies)) {
					$instance = $this->create($dependencies[$paramName]);
				} elseif ($this->hasDefinitionForClass($name, $instanceName)) {
					$instance = $this->createByInterface($instanceName, [], [], null, $name);
				} elseif (array_key_exists($instanceName, $diMap)) {
					$instance = $this->create($diMap[$instanceName]);
				} else {
					$instance = $this->createByInterface($instanceName, [], [], null, $name);
				}
			} else {
				$instance = $this->create($instanceName);
			}

			if (!$instance) {
				continue;
			}

			$config[$paramName] = $instance;
		}

		return $reflection->newInstance($config);
	}

	private function hasDefinitionForClass(string $className, string $interfaceName): bool
	{
		if (!array_key_exists($className, $this->classes)) {
			return false;
		}

		if (!array_key_exists($interfaceName, $this->classes[$className])) {
			return false;
		}

		return true;
	}

    private function findClassForInterface(string $interfaceName, ?string $contextClassName = null): ?string
    {
        $classForInterface = null;
        if ($contextClassName !== null && array_key_exists($contextClassName, $this->classes)) {
            $classData = $this->classes[$contextClassName];
            if (array_key_exists($interfaceName, $classData)) {
                $classForInterface = $classData[$interfaceName];
            }
        }

        if (!$classForInterface) {
            if (array_key_exists($interfaceName, $this->interfaces)) {
                $classForInterface = $this->interfaces[$interfaceName];
            }
        }

        return $classForInterface;
    }
    
    private function setDefaults(array $map): void
    {
        foreach ($map as $interface => $class) {
            if (!array_key_exists($interface, $this->interfaces)) {
                $this->interfaces[$interface] = $class;
            }
        }
    }

    private function typeIsPrimitive(string $name): bool
    {
        return in_array($name, [
            'bool',
            'boolen',
            'int',
            'integer',
            'float',
            'string',
            'array',
            'callable',
            'iterable',
            'resource',
            'mixed',
        ]) !== false;
    }
}
