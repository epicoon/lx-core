<?php

namespace lx;

abstract class AbstractJsModuleInjector implements JsModuleInjectorInterface, FusionComponentInterface
{
    use FusionComponentTrait;

    protected array $modules = [];

    abstract public function resolveModuleName(string $moduleName): string;

    protected function hasModuleName(string $moduleName): bool
    {
        return array_key_exists($moduleName, $this->modules);
    }

    protected function getModuleName(string $moduleName): string
    {
        return $this->modules[$moduleName];
    }
}
