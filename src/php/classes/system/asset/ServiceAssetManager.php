<?php

namespace lx;

use lx;

class ServiceAssetManager extends AbstractAssetManager
{
    public function resolveModuleName(string $moduleName): string
    {
        if ($this->hasModuleName($moduleName)) {
            return $this->getModuleName($moduleName);
        }

        return lx::$app->assetManager->resolveModuleName($moduleName);
    }
}
