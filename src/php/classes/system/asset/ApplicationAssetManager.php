<?php

namespace lx;

class ApplicationAssetManager extends AbstractAssetManager
{
    protected array $cssPresets = [
        'white' => 'lx.CssPresetWhite',
        'dark' => 'lx.CssPresetDark',
    ];

    protected ?string $defaultCssPreset = 'white';
}
