<?php

namespace lx;

class PluginAssetManager extends AbstractAssetManager
{
    public function resolveModuleName(string $moduleName): string
    {
        if ($this->hasModuleName($moduleName)) {
            return $this->getModuleName($moduleName);
        }

        /** @var Plugin $plugin */
        $plugin = $this->owner;
        return $plugin->getService()->assetManager->resolveModuleName($moduleName);
    }
}
