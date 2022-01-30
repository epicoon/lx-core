<?php

namespace lx;

interface JsModuleInjectorInterface
{
    public function resolveModuleName(string $moduleName): string;
}
