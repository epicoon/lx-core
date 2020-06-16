<?php

namespace lx;

/**
 * Trait ObjectTrait
 * @package lx
 */
trait ObjectTrait
{
    /** @var array */
    private $delegateList = [];

    /**
     * @return array
     */
    public static function getConfigProtocol()
    {
        return [];
    }

    /**
     * @return array
     */
    public static function diMap()
    {
        return [];
    }

    /**
     * @return bool
     */
    public static function isSingleton()
    {
        return false;
    }

    /**
     * ObjectTrait constructor.
     * @param array $config
     */
    public function __construct($config = [])
    {
        $this->__objectConstruct($config);
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    public function __get($name)
    {
        return $this->__objectGet($name);
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed|null
     */
    public function __call($name, $arguments)
    {
        return $this->__objectCall($name, $arguments);
    }

    /**
     * @return bool
     */
    public function isLxObject()
    {
        return true;
    }


    /*******************************************************************************************************************
     * NOT PUBLIC
     ******************************************************************************************************************/

    /**
     * @param array $config
     */
    protected function __objectConstruct($config = [])
    {
        if ($this->validateConfig($config)) {
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
     * @param string $name
     * @return mixed|null
     */
    protected function __objectGet($name)
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
     * @param string $name
     * @param array $arguments
     * @return mixed|null
     */
    protected function __objectCall($name, $arguments)
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
    protected function delegateMethodsCall($fields)
    {
        if (is_string($fields)) {
            $fields = [$fields];
        }

        if (is_array($fields)) {
            $this->delegateList = $fields;
        }
    }

    /**
     * @param array $config
     * @return bool
     */
    private function validateConfig($config)
    {
        $protocol = static::getConfigProtocol();
        if (empty($protocol)) {
            return true;
        }

        foreach ($protocol as $paramName => $paramDescr) {
            $required = $paramDescr['required'] ?? false;
            $class = is_string($paramDescr)
                ? $paramDescr
                : ($paramDescr['instance'] ?? null);

            if ( ! array_key_exists($paramName, $config)) {
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

            if (property_exists($this, $paramName)) {
                $this->$paramName = $param;
            }
        }

        return true;
    }
}
