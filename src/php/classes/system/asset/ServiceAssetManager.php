<?php

namespace lx;

use lx;

class ServiceAssetManager extends AbstractAssetManager
{
    public function resolveModuleName(string $moduleName): string
    {
        if (!$this->hasModuleName($moduleName)) {
            return lx::$app->assetManager->resolveModuleName($moduleName);
        }

        return $this->getModuleName($moduleName);
    }

    public function getCssPresets(): array
    {
        if (empty($this->cssPresets)) {
            return lx::$app->assetManager->getCssPresets();
        }

        return $this->cssPresets;
    }

    public function getCssPresetModule(string $name): ?string
    {
        if (!array_key_exists($name, $this->cssPresets)) {
            return lx::$app->assetManager->getCssPresetModule($name);
        }

        return $this->cssPresets[$name];
    }

    public function getDefaultCssPreset(): string
    {
        if ($this->defaultCssPreset === null) {
            return lx::$app->assetManager->getDefaultCssPreset();
        }

        return $this->defaultCssPreset;
    }
}
