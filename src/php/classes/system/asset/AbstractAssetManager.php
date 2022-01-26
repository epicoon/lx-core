<?php

namespace lx;

abstract class AbstractAssetManager implements AssetManagerInterface, FusionComponentInterface
{
    use FusionComponentTrait;

    protected array $modules = [];
    protected array $cssPresets = [];
    protected ?string $defaultCssPreset = null;
    
    public function resolveModuleName(string $moduleName): string
    {
        if ($this->hasModuleName($moduleName)) {
            return $this->getModuleName($moduleName);
        }

        return $moduleName;
    }
    
    public function getCssPresets(): array
    {
        return $this->cssPresets;
    }

    public function getCssPresetModule(string $name): ?string
    {
        return $this->cssPresets[$name] ?? null;
    }
    
    public function getDefaultCssPreset(): string
    {
        return $this->defaultCssPreset;
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
