<?php

namespace lx;

interface CssManagerInterface
{
    public function getCssContexts(): array;
    public function getCssAssets(): array;
    public function getCssPresets(): array;
    public function getCssPresetModule(string $name): ?string;
    public function getDefaultCssPresetName(): string;
    public function getBuildType(): string;
    public function isBuildType(string $type): bool;
}
