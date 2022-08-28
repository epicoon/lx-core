<?php

namespace lx;

interface CssManagerInterface
{
    public function getCssPresets(): array;
    public function getCssPresetModule(string $name): ?string;
    public function getDefaultCssPreset(): string;
    public function getBuildType(): string;
    public function isBuildType(string $type): bool;
}
