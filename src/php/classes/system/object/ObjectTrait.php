<?php

namespace lx;

use lx;

trait ObjectTrait
{
    private array $delegateList = [];
    private array $objectDependencies = [];

    public static function getDependenciesConfig(): array
    {
        return [];
    }

    public static function getDependenciesDefaultMap(): array
    {
        return [];
    }

    public static function isSingleton(): bool
    {
        return false;
    }
    
    public function __construct(iterable $config = [])
    {
        $this->__objectConstruct($config);
    }
    
    public static function constructWithDependencies(iterable $params, iterable $dependencies): ObjectInterface
    {
        //TODO ??????????????????????????????????????????
        
    }

    /**
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->__objectGet($name);
    }

    /**
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        return $this->__objectCall($name, $arguments);
    }


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * NOT PUBLIC
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    protected function __objectConstruct(iterable &$config = []): void
    {
        if ($this->applyConfig($config)) {
            $traits = ObjectReestr::getTraitMap(static::class);
            foreach ($traits as $traitName) {
                $trait = ObjectReestr::getTraitInfo($traitName);
                if (array_key_exists('__construct', $trait)) {
                    $this->{$trait['__construct']}($config);
                }
            }
        }
    }

    /**
     * @return mixed
     */
    protected function __objectGet(string $name)
    {
        $traits = ObjectReestr::getTraitMap(static::class);
        foreach ($traits as $traitName) {
            $trait = ObjectReestr::getTraitInfo($traitName);
            if (array_key_exists('__get', $trait)) {
                $result = $this->{$trait['__get']}($name);
                if ($result !== null) {
                    return $result;
                }
            }
        }

        if (ClassHelper::publicPropertyExists($this, $name)) {
            return $this->$name;
        }

        $definition = $this->getDependencyDefinition($name);
        if ($definition && ($definition['readable'] ?? false)) {
            return $this->getReadableDependency($name);
        }

        return null;
    }

    /**
     * @return mixed
     */
    protected function __objectCall(string $name, array $arguments)
    {
        if (ClassHelper::publicMethodExists($this, $name)) {
            return call_user_func_array([$this, $name], $arguments);
        }

        foreach ($this->delegateList as $field) {
            if (!property_exists($this, $field)) {
                continue;
            }

            $value = $this->$field;
            if (!is_object($value)) {
                continue;
            }

            if (ClassHelper::publicMethodExists($value, $name)) {
                return call_user_func_array([$value, $name], $arguments);
            }
        }

        \lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
            '__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
            'msg' => "Method '$name' does not exist",
            'origin_class' => static::class,
        ]);
        return null;
    }

    /**
     * @param array|string $fields
     */
    protected function delegateMethodsCall($fields): void
    {
        if (is_string($fields)) {
            $fields = [$fields];
        }

        if (is_array($fields)) {
            $this->delegateList = $fields;
        }
    }

    private function applyConfig(array $config): bool
    {
        $protocol = static::getDependenciesConfig();
        if (empty($protocol)) {
            return true;
        }

        foreach ($protocol as $paramName => $paramDescr) {
            $paramDescr = $this->getDependencyDefinition($paramName);
            if (array_key_exists($paramName, $config)) {
                $param = $config[$paramName];
                $class = $paramDescr['instance'] ?? null;
                if ($class && !ClassHelper::checkInstance($param, $class)) {
                    $contextClass = static::class;
                    \lx::devLog(['_'=>[__FILE__,__CLASS__,__TRAIT__,__METHOD__,__LINE__],
                        '__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
                        'msg' => "Class '$contextClass' has received dependency '$paramName' with wrong type. Type '$config' expected.",
                    ]);
                    return false;
                }
                $config[$paramName] = $param;
                continue;
            }

            if ($paramDescr['lazy'] ?? false) {
                continue;
            }

            $param = $this->createDependencyInstance($paramName);
            if ($param === null) {
                return false;
            }
            $config[$paramName] = $param;
        }
        
        foreach ($config as $paramName => $value) {
            $this->setParameter($paramName, $value);
        }

        return true;
    }

    /**
     * @param mixed $value
     */
    private function setParameter(string $name, $value): void
    {
        $setterName = 'init' . ucfirst($name);
        if (method_exists($this, $setterName)) {
            $this->$setterName($value);
            return;
        }

        if (ClassHelper::privatePropertyExists($this, $name)) {
            //TODO devlog?
            return;
        }

        if (property_exists($this, $name)) {
            $this->$name = $value;
        }

        $definition = $this->getDependencyDefinition($name);
        if ($definition && ($definition['readable'] ?? false)) {
            $this->objectDependencies[$name] = $value;
        }
    }

    private function getDependencyDefinition(string $name): ?array
    {
        $protocol = static::getDependenciesConfig();
        if (!array_key_exists($name, $protocol)) {
            return null;
        }

        $definition = $protocol[$name];
        $definition = is_string($definition)
            ? ['instance' => $definition]
            : $definition;

        //TODO неочевидно, что ленивая загрузка обязательно делает зависимость читаемым полем
        if ($definition['lazy'] ?? false) {
            $definition['readable'] = true;
        }

        return $definition;
    }

    /**
     * @return mixed
     */
    private function getReadableDependency(string $name)
    {
        if (array_key_exists($name, $this->objectDependencies)) {
            return $this->objectDependencies[$name];
        }

        $definition = $this->getDependencyDefinition($name);
        if ($definition && ($definition['lazy'] ?? false)) {
            $param = $this->createDependencyInstance($name);
            if ($param !== null) {
                $this->setParameter($name, $param);
                return $this->objectDependencies[$name] ?? null;
            }
        }

        return null;
    }

    /**
     * @return mixed
     */
    private function createDependencyInstance(string $name)
    {
        $definition = $this->getDependencyDefinition($name);
        if (array_key_exists('default', $definition)) {
            return is_callable($definition['default'])
                ? $definition['default']()
                : $definition['default'];
        }

        $contextClass = static::class;
        $class = $definition['instance'] ?? null;
        if (!$class) {
            \lx::devLog(['_'=>[__FILE__,__CLASS__,__TRAIT__,__METHOD__,__LINE__],
                '__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
                'msg' => "Class '$contextClass' has undefined dependency '$paramName'",
            ]);
            return null;
        }

        return lx::$app->diProcessor->build()
            ->setClass($class)
            ->setContextClass($contextClass)
            ->setContextWeakDependencies(static::getDependenciesDefaultMap())
            ->getInstance();
    }
}
