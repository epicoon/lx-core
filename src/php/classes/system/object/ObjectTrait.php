<?php

namespace lx;

trait ObjectTrait
{
    private array $delegateList = [];

    public static function getConfigProtocol(): array
    {
        return [];
    }

    public static function diMap(): array
    {
        return [];
    }

    public static function isSingleton(): bool
    {
        return false;
    }
    
    public function __construct(array $config = [])
    {
        $this->__objectConstruct($config);
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

    public function isLxObject(): bool
    {
        return true;
    }


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * NOT PUBLIC
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    protected function __objectConstruct(array $config = []): array
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
        return $config;
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
        $protocol = static::getConfigProtocol();
        if (empty($protocol)) {
            return true;
        }

        foreach ($protocol as $paramName => $paramDescr) {
            $required = $paramDescr['required'] ?? false;
            if (!array_key_exists($paramName, $config)) {
                if ($required) {
                    $className = static::class;
                    \lx::devLog(['_'=>[__FILE__,__CLASS__,__TRAIT__,__METHOD__,__LINE__],
                        '__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
                        'msg' => "Class '$className' require '$paramName' parameter",
                    ]);
                    return false;
                }

                continue;
            }

            $class = is_string($paramDescr)
                ? $paramDescr
                : ($paramDescr['instance'] ?? null);
            if ($class) {
                $param = $config[$paramName];
                if (!($param instanceof $class)) {
                    $className = static::class;
                    \lx::devLog(['_'=>[__FILE__,__CLASS__,__TRAIT__,__METHOD__,__LINE__],
                        '__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
                        'msg' => "Class '$className' has gotten wrong parameter instance for '$paramName'",
                    ]);
                    return false;
                }
            } else {
                $param = $config[$paramName];
            }

            $this->setParameter($paramName, $param);
            unset($config[$paramName]);
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
            return;
        }

        if (property_exists($this, $name)) {
            $this->$name = $value;
        }
    }
}
