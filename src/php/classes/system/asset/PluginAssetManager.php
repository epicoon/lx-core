<?php

namespace lx;

class PluginAssetManager extends AbstractAssetManager
{
    public function resolveModuleName(string $moduleName): string
    {
        if (!$this->hasModuleName($moduleName)) {
            /** @var Plugin $plugin */
            $plugin = $this->owner;
            return $plugin->getService()->assetManager->resolveModuleName($moduleName);
        }

        return $this->getModuleName($moduleName);
    }

    public function getCssPresets(): array
    {
        if (empty($this->cssPresets)) {
            /** @var Plugin $plugin */
            $plugin = $this->owner;
            return $plugin->getService()->assetManager->getCssPresets();
        }
        
        return $this->cssPresets;
    }

    public function getCssPresetModule(string $name): ?string
    {
        if (!array_key_exists($name, $this->cssPresets)) {
            /** @var Plugin $plugin */
            $plugin = $this->owner;
            return $plugin->getService()->assetManager->getCssPresetModule($name);
        }

        return $this->cssPresets[$name];
    }

    public function getDefaultCssPreset(): string
    {
        if ($this->defaultCssPreset === null) {
            /** @var Plugin $plugin */
            $plugin = $this->owner;
            return $plugin->getService()->assetManager->getDefaultCssPreset();
        }

        return $this->defaultCssPreset;
    }
}
