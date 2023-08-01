<?php

namespace lx;

use ReflectionClass;
use ReflectionNamedType;

class ObjectMapper
{
    /** @var mixed */
    private $object;
    private array $ignoredInstances = [];
    private array $propertiesForSkip = [];
    private array $parsedObjects = [];
    private array $parsedKeys = [];
    private RecursiveTree $tree;

    /**
     * @param mixed $object
     */
    public function setObject($object): ObjectMapper
    {
        $this->object = $object;
        return $this;
    }

    public function ignoreInstanses(array $list): ObjectMapper
    {
        $this->ignoredInstances = $list;
        return $this;
    }
    
    public function skipProperties(array $list): ObjectMapper
    {
        $this->propertiesForSkip = $list;
        return $this;
    }

    public function getResult(): RecursiveTree
    {
        $this->tree = RecursiveTree::create();
        $this->tree->setData([
            'name' => '__head__',
            'type' => 'object: ' . get_class($this->object),
        ]);
        $this->parsedObjects[] = $this->object;
        $this->parsedKeys[] = $this->tree->getKey();

        $this->parseObjectProperties($this->object, $this->tree, $this->propertiesForSkip);
        return $this->tree;
    }

    private function parseItem(
        string $key,
        /* mixed */ $val,
        RecursiveTree $node,
        array $propertiesForSkip,
        ?ReflectionNamedType $type = null
    ): void
    {
        switch (true) {
            case is_object($val):
                $this->processObject($key, $val, $node, $propertiesForSkip);
                break;
            case is_array($val):
                $this->processArray($key, $val, $node, $propertiesForSkip);
                break;
            default:
                if ($type === null) {
                    $type = $this->defineType($val);
                } else {
                    $type = $type->getName();
                }
                $valNode = $node->add();
                $valNode->setData([
                    'name' => $key,
                    'type' => $type,
                    'value' => $this->getScalarValue($val),
                ]);
        }
    }

    private function processObject(
        string $key,
        /* mixed */ $val,
        RecursiveTree $node,
        array $propertiesForSkip
    ): void
    {
        $index = array_search($val, $this->parsedObjects, true);
        if ($index !== false) {
            $nodeKey = $this->parsedKeys[$index];
            $valNode = $node->getNode($nodeKey);
            $node->add($valNode);
            return;
        }

        $valNode = $node->add();
        $valNode->setData([
            'name' => $key,
            'type' => 'object: ' . get_class($val),
        ]);
        $this->parsedObjects[] = $val;
        $this->parsedKeys[] = $valNode->getKey();

        $forSkip = [];
        if (array_key_exists($key, $propertiesForSkip) && is_array($propertiesForSkip[$key])) {
            $forSkip = $propertiesForSkip[$key];
        }
        $this->parseObjectProperties($val, $valNode, $forSkip);
    }

    private function parseObjectProperties($object, RecursiveTree $node, array $propertiesForSkip): void
    {
        $reflect = new ReflectionClass($object);
        $props = $reflect->getProperties();
        foreach ($props as $prop) {
            $propName = $prop->getName();
            if (in_array($propName, $propertiesForSkip)) {
                continue;
            }

            $prop->setAccessible(true);
            $val = $prop->getValue($object);
            if ($this->checkIgnore($val)) {
                continue;
            }

            $this->parseItem($propName, $val, $node, $propertiesForSkip, $prop->getType());
        }
    }

    private function processArray(
        string $key,
        /* mixed */ $val,
        RecursiveTree $node,
        array $propertiesForSkip
    ): void
    {
        $valNode = $node->add();
        $valNode->setData([
            'name' => $key,
            'type' => 'array',
        ]);
        $forSkip = [];
        if (array_key_exists($key, $propertiesForSkip) && is_array($propertiesForSkip[$key])) {
            $forSkip = $propertiesForSkip[$key];
        }
        $this->parseArrayItems($val, $valNode, $forSkip);
    }

    private function parseArrayItems(array $arr, RecursiveTree $node, array $forSkip): void
    {
        foreach ($arr as $key => $value) {
            if (in_array($key, $forSkip) || $this->checkIgnore($value)) {
                continue;
            }

            $this->parseItem($key, $value, $node, $forSkip);
        }
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

    /**
     * @param mixed $object
     */
    private function checkIgnore($object): bool
    {
        if (!is_object($object)) {
            return false;
        }

        foreach ($this->ignoredInstances as $instance) {
            if ($object instanceof $instance) {
                return true;
            }
        }

        return false;
    }
}
