<?php

namespace lx;

use ReflectionClass;
use ReflectionNamedType;

class ObjectMapper
{
    /** @var mixed */
    private $object;
    private array $propertiesForSkip = [];
    private array $parsed = [];

    /**
     * @param mixed $object
     */
    public function setObject($object): ObjectMapper
    {
        $this->object = $object;
        return $this;
    }

    public function skipProperties(array $list): ObjectMapper
    {
        $this->propertiesForSkip = $list;
        return $this;
    }

    public function getResult(): array
    {
        $this->parsed[] = $this->object;
        $result = [
            'name' => '__head__',
            'type' => 'object: ' . get_class($this->object),
            'fields' => $this->parseObjectProperties($this->object, $this->propertiesForSkip),
        ];
        return $result;
    }

    private function parseObjectProperties($object, array $propertiesForSkip): array
    {
        $reflect = new ReflectionClass($object);
        $props = $reflect->getProperties();
        $result = [];
        foreach ($props as $prop) {
            $propName = $prop->getName();
            if (in_array($propName, $propertiesForSkip)) {
                continue;
            }

            $prop->setAccessible(true);
            $val = $prop->getValue($object);
            if (is_object($val)) {
                if (in_array($val, $this->parsed)) {
                    continue;
                } else {
                    $this->parsed[] = $val;
                }
            }

            $result[$propName] = $this->parseItem($propName, $val, $propertiesForSkip, $prop->getType());
        }

        return $result;
    }

    private function parseArrayItems(array $arr, array $forSkip): array
    {
        $result = [];
        foreach ($arr as $key => $value) {
            if (in_array($key, $forSkip)) {
                continue;
            }
            if (is_object($value)) {
                if (in_array($value, $this->parsed)) {
                    continue;
                } else {
                    $this->parsed[] = $value;
                }
            }
            $result[$key] = $this->parseItem($key, $value, $forSkip);
        }
        return $result;
    }

    private function parseItem(string $key, $val, array $propertiesForSkip, ?ReflectionNamedType $type = null): array
    {
        if (is_object($val)) {
            $forSkip = [];
            if (array_key_exists($key, $propertiesForSkip) && is_array($propertiesForSkip[$key])) {
                $forSkip = $propertiesForSkip[$key];
            }
            return [
                'name' => $key,
                'type' => 'object: ' . get_class($val),
                'fields' => $this->parseObjectProperties($val, $forSkip),
            ];
        }

        if (is_array($val)) {
            $forSkip = [];
            if (array_key_exists($key, $propertiesForSkip) && is_array($propertiesForSkip[$key])) {
                $forSkip = $propertiesForSkip[$key];
            }
            return [
                'name' => $key,
                'type' => 'array',
                'fields' => $this->parseArrayItems($val, $forSkip),
            ];
        }

        if ($type === null) {
            $type = $this->defineType($val);
        } else {
            $type = $type->getName();
        }
        return [
            'name' => $key,
            'type' => $type,
            'value' => $this->getScalarValue($val),
        ];
    }

    /**
     * @param mixed $val
     * @return mixed
     */
    private function getScalarValue($val)
    {
        if ($val === true) {
            return '&lt;true&gt;';
        }

        if ($val === false) {
            return '&lt;false&gt;';
        }

        if ($val === null) {
            return '&lt;null&gt;';
        }

        return $val;
    }

    /**
     * @param mixed $val
     */
    private function defineType($val): string
    {
        if ($val === null) {
            return 'null';
        }

        if ($val === true || $val === false) {
            return 'bool';
        }

        if (filter_var($val, FILTER_VALIDATE_INT)) {
            return 'int';
        }

        if (filter_var($val, FILTER_VALIDATE_FLOAT)) {
            return 'float';
        }

        return 'string';
    }
}
