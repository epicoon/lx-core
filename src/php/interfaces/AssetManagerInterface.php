<?php

namespace lx;

interface AssetManagerInterface
{
    public function resolveModuleName(string $moduleName): string;
}
