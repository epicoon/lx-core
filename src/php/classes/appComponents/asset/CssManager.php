<?php

namespace lx;

use lx;

class CssManager implements
    CssManagerInterface,
    FusionComponentInterface,
    JsModuleClientInterface,
    ClientComponentInterface
{
    use FusionComponentTrait;
    
    const BUILD_TYPE_NONE = 'none';
    const BUILD_TYPE_ALL_TOGETHER = 'all';
    const BUILD_TYPE_SEGREGATED = 'segregated';

    protected array $cssContexts = [
        'lx.BasicProxyCssContext',
    ];
    protected array $cssAssets = [
        'lx.SourceCssContext',
    ];
    protected array $cssPresets = [
        'white' => 'lx.CssPresetWhite',
        'dark' => 'lx.CssPresetDark',
    ];
    protected string $defaultCssPreset = 'white';
    protected string $buildType = self::BUILD_TYPE_ALL_TOGETHER;

    public function getJsModules(): array
    {
        return $this->cssContexts;
    }

    public function getCLientData(): array
    {
        $presetedFile = AppAssetCompiler::getAppPresetedFile();
        $presetedList = json_decode($presetedFile->get(), true);

        $modules = lx::$app->jsModules->getCoreModules();
        $presetedList = array_merge($presetedList, lx::$app->jsModules->getPresetedCssClasses($modules));

        return [
            'assetBuildType' => $this->getBuildType(),
            'cssPreset' => $this->getDefaultCssPresetName(),
            'cssPresets' => $this->getCssPresets(),
            'cssContexts' => $this->cssContexts,
            'preseted' => $presetedList,
        ];
    }

    public function getCssContexts(): array
    {
        return $this->cssContexts;
    }

    public function getCssAssets(): array
    {
        return $this->cssAssets;
    }

    public function getCssPresets(): array
    {
        return $this->cssPresets;
    }

    public function getCssPresetModule(string $name): ?string
    {
        return $this->cssPresets[$name] ?? null;
    }
    
    public function getDefaultCssPresetName(): string
    {
        return $this->defaultCssPreset;
    }

    public function getBuildType(): string
    {
        return $this->buildType;
    }

    public function isBuildType(string $type): bool
    {
        return $this->buildType == $type;
    }
}
