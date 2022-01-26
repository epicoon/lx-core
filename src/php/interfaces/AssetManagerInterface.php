<?php

namespace lx;

interface AssetManagerInterface
{
    public function resolveModuleName(string $moduleName): string;
    public function getCssPresets(): array;
    public function getCssPresetModule(string $name): ?string;
    public function getDefaultCssPreset(): string;
}
