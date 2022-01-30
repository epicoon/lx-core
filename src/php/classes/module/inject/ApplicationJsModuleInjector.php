<?php

namespace lx;

class ApplicationJsModuleInjector extends AbstractJsModuleInjector
{
    public function resolveModuleName(string $moduleName): string
    {
        if ($this->hasModuleName($moduleName)) {
            return $this->getModuleName($moduleName);
        }

        return $moduleName;
    }
}
