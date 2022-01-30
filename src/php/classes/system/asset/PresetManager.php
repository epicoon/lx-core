<?php

namespace lx;

class PresetManager implements PresetManagerInterface, FusionComponentInterface
{
    use FusionComponentTrait;
    
    const BUILD_TYPE_NONE = 'none';
    const BUILD_TYPE_ALL_TOGETHER = 'all';
    const BUILD_TYPE_SEGREGATED = 'segregated';

    protected array $cssPresets = [
        'white' => 'lx.CssPresetWhite',
        'dark' => 'lx.CssPresetDark',
    ];
    protected ?string $defaultCssPreset = 'white';
    protected string $buildType = self::BUILD_TYPE_ALL_TOGETHER;
    
    public function getCssPresets(): array
    {
        return $this->cssPresets;
    }

    public function getCssPresetModule(string $name): ?string
    {
        return $this->cssPresets[$name] ?? null;
    }
    
    public function getDefaultCssPreset(): string
    {
        return $this->defaultCssPreset;
    }

    public function isBuildType(string $type): bool
    {
        return $this->buildType == $type;
    }
}
