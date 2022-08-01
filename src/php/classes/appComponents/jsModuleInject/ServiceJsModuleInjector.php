<?php

namespace lx;

use lx;

class ServiceJsModuleInjector extends AbstractJsModuleInjector
{
    public function resolveModuleName(string $moduleName): string
    {
        if (!$this->hasModuleName($moduleName)) {
            return lx::$app->moduleInjector->resolveModuleName($moduleName);
        }

        return $this->getModuleName($moduleName);
    }
}
