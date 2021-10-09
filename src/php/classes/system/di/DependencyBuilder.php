<?php

namespace lx;

use lx;

class DependencyBuilder
{
    private ?string $class = null;
    private ?string $defaultClass = null;
    private ?string $interface = null;
    private array $params = [];
    private array $strongDependencies = [];
    private array $weakDependencies = [];
    private ?string $contextClass = null;
    private array $contextStrongDependencies = [];
    private array $contextWeakDependencies = [];
    
    public function setClass(string $class): DependencyBuilder
    {
        $this->class = $class;
        return $this;
    }

    public function setDefaultClass(string $class): DependencyBuilder
    {
        $this->defaultClass = $class;
        return $this;
    }

    public function setInterface(string $interface): DependencyBuilder
    {
        $this->interface = $interface;
        return $this;
    }

    public function setParams(array $params): DependencyBuilder
    {
        $this->params = $params;
        return $this;
    }

    public function setStrongDependencies(array $dependencies): DependencyBuilder
    {
        $this->strongDependencies = $dependencies;
        return $this;
    }

    public function setWeakDependencies(array $dependencies): DependencyBuilder
    {
        $this->weakDependencies = $dependencies;
        return $this;
    }

    public function setContextClass(string $class): DependencyBuilder
    {
        $this->contextClass = $class;
        return $this;
    }

    public function setContextStrongDependencies(array $dependencies): DependencyBuilder
    {
        $this->contextStrongDependencies = $dependencies;
        return $this;
    }

    public function setContextWeakDependencies(array $dependencies): DependencyBuilder
    {
        $this->contextWeakDependencies = $dependencies;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getInstance()
    {
        if ($this->interface) {
            return lx::$app->diProcessor->createByInterface(
                $this->interface,
                $this->params,
                $this->strongDependencies,
                $this->weakDependencies,
                $this->defaultClass,
                $this->contextClass,
                $this->contextStrongDependencies,
                $this->contextWeakDependencies
            );
        }
        
        if ($this->class) {
            return lx::$app->diProcessor->create(
                $this->class,
                $this->params,
                $this->strongDependencies,
                $this->weakDependencies,
                $this->contextClass,
                $this->contextStrongDependencies,
                $this->contextWeakDependencies
            );
        }

        return null;
    }
}
