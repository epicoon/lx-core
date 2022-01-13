<?php

namespace lx;

abstract class AbstractAssetManager implements AssetManagerInterface, FusionComponentInterface
{
    use FusionComponentTrait;

    protected array $modules = [
        'lx.CssColorSchema' => 'lx.CssColorSchema.white',
    ];

    public function resolveModuleName(string $moduleName): string
    {
        if ($this->hasModuleName($moduleName)) {
            return $this->getModuleName($moduleName);
        }

        return $moduleName;
    }

    protected function hasModuleName(string $moduleName): bool
    {
        return array_key_exists($moduleName, $this->modules);
    }

    protected function getModuleName(string $moduleName): string
    {
        return $this->modules[$moduleName];
    }
}
