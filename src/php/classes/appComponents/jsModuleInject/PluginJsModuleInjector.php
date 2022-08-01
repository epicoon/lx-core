<?php

namespace lx;

class PluginJsModuleInjector extends AbstractJsModuleInjector
{
    public function resolveModuleName(string $moduleName): string
    {
        if (!$this->hasModuleName($moduleName)) {
            /** @var Plugin $plugin */
            $plugin = $this->owner;
            return $plugin->getService()->moduleInjector->resolveModuleName($moduleName);
        }

        return $this->getModuleName($moduleName);
    }
}
