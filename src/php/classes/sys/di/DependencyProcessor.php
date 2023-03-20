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
            HttpResponseInterface::class => HttpResponse::class,
            HtmlRendererInterface::class => HtmlRenderer::class,
            HtmlTemplateProviderInterface::class => HtmlTemplateProvider::class,
            UserInterface::class => User::class,
        ]);
    }
    
    public function build(): DependencyBuilder
    {
        return new DependencyBuilder();
    }

    /**
     * @return mixed
     */
    public function create(
        string $classOrInterface,
        array $params = [],
        array $strongDependencies = [],
        array $weakDependencies = [],
        ?string $contextClass = null,
        array $contextStrongDependencies = [],
        array $contextWeakDependencies = []
    ) {
        try {
            $re = new \ReflectionClass($classOrInterface);
        } catch (\Throwable $exception) {
            throw new \Exception("Class $classOrInterface does not exist");
        }

        if ($re->isInterface()) {
            return $this->createByInterface(
                $classOrInterface,
                $params,
                $strongDependencies,
                $weakDependencies,
                null,
                $contextClass,
                $contextStrongDependencies,
                $contextWeakDependencies
            );
        }

        $isSingleton = false;
        if ($re->hasMethod('isSingleton')) {
            $method = $re->getMethod('isSingleton');
            $isSingleton = $method->invoke(null);
            if ($isSingleton && array_key_exists($classOrInterface, $this->instances)) {
                return $this->instances[$classOrInterface];
            }
        }

        try {
            $instance = $this->createProcess($re, $params, $strongDependencies, $weakDependencies);
        } catch (\Throwable $exception) {
            throw new \Exception($exception->getMessage());
        }

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
        array $strongDependencies = [],
        array $weakDependencies = [],
        ?string $defaultClass = null,
        ?string $contextClass = null,
        array $contextStrongDependencies = [],
        array $contextWeakDependencies = []
    ) {
        if ($interface === null) {
            $classForInterface = $defaultClass;
        } else {
            $classForInterface = $this->getClassForInterface(
                $interface,
                $contextClass,
                $contextStrongDependencies,
                $contextWeakDependencies
            );
            if (!$classForInterface) {
                $this->useServiceByInterface($interface);
                $classForInterface = $this->getClassForInterface(
                    $interface,
                    $contextClass,
                    $contextStrongDependencies,
                    $contextWeakDependencies
                );
            }
            if (!$classForInterface) {
                $classForInterface = $defaultClass;
            }
        }

        if (!$classForInterface) {
            return null;
        }

        return $this->create($classForInterface, $params, $strongDependencies, $weakDependencies);
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


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * PRIVATE
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * @return mixed
     */
    private function createProcess(
        ReflectionClass $re,
        array $params,
        array $strongDependencies,
        array $weakDependencies
    ) {
        if ($re->implementsInterface(ObjectInterface::class)) {
            $objectParams = empty($params) ? [] : $params[array_keys($params)[0]];
            if (!empty($strongDependencies)) {
                $objectParams['__strongDependencies__'] = $strongDependencies;
            }
            if (!empty($weakDependencies)) {
                $objectParams['__weakDependencies__'] = $weakDependencies;
            }
            return $re->newInstanceArgs([$objectParams]);
        }

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

        $contextClassName = $re->getName();
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
            if ($this->typeIsPrimitive($type->getName())) {
                $default = $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : new Undefined();
                if ($default instanceof Undefined) {
                    throw new \Exception("DI-processor can't create an instance of the $contextClassName"
                        .": the typed parameter {$parameter->getName()} has to have a default value");
                }

                $finalParams[$i] = $default;
                continue;
            }

            $typeName = $type->getName();
            $typeRe = new \ReflectionClass($typeName);
            if ($typeRe->isInterface()) {
                if (array_key_exists($typeName, $strongDependencies)) {
                    $instance = $this->create($strongDependencies[$typeName], [], [], [], $contextClassName);
                } elseif (array_key_exists($paramName, $strongDependencies)) {
                    $instance = $this->create($strongDependencies[$paramName], [], [], [], $contextClassName);

                } elseif ($this->hasDefinitionForClass($name, $typeName)) {
                    $instance = $this->createByInterface($typeName, [], [], [], null, $contextClassName);

                } elseif (array_key_exists($typeName, $weakDependencies)) {
                    $instance = $this->create($weakDependencies[$typeName], [], [], [], $contextClassName);
                } elseif (array_key_exists($paramName, $weakDependencies)) {
                    $instance = $this->create($weakDependencies[$paramName], [], [], [], $contextClassName);

                } else {
                    $instance = $this->createByInterface($typeName, [], [], [], null, $contextClassName);
                }
            } else {
                $instance = $this->create($typeName, [], [], [], $contextClassName);
            }

            $finalParams[$i] = $instance;
        }

        $object = $re->newInstanceArgs($finalParams);
        return $object;
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

    private function getClassForInterface(
        ?string $interfaceName,
        ?string $contextClassName,
        array $contextStrongDependencies,
        array $contextWeakDependencies
    ): ?string
    {
        if ($interfaceName === null) {
            return null;
        }

        if (array_key_exists($interfaceName, $contextStrongDependencies)) {
            return $contextStrongDependencies[$interfaceName];
        }

        if ($contextClassName !== null && array_key_exists($contextClassName, $this->classes)) {
            $classData = $this->classes[$contextClassName];
            if (array_key_exists($interfaceName, $classData)) {
                return $classData[$interfaceName];
            }
        }

        if (array_key_exists($interfaceName, $contextWeakDependencies)) {
            return $contextWeakDependencies[$interfaceName];
        }

        if (array_key_exists($interfaceName, $this->interfaces)) {
            return $this->interfaces[$interfaceName];
        }

        return null;
    }
    
    private function useServiceByInterface(string $interface): void
    {
        $service = ClassHelper::defineService($interface);
        if (!$service || in_array($service->name, $this->addedServices)) {
            return;
        }
        
        $map = $service->getConfig('diProcessor') ?? [];
        $this->interfaces = ArrayHelper::mergeRecursiveDistinct($this->interfaces, $map['interfaces'] ?? []);
        $this->classes = ArrayHelper::mergeRecursiveDistinct($this->classes, $map['classes'] ?? []);
        $this->addedServices[] = $service->name;
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
