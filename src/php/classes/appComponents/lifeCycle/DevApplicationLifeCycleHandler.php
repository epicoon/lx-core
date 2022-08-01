<?php

namespace lx;

use lx;

class DevApplicationLifeCycleHandler extends AbstractApplicationLifeCycleHandler
{
    public function beforeApplicationRun(): void
    {
        $compiler = new AssetCompiler();

        $compiler->compileLxCss();

        $coreCode = $compiler->compileJsCore();
        $file = new File(lx::$conductor->webJs . '/core.js');
        $file->put($coreCode);
    }

    public function afterApplicationRun(): void
    {
        // pass
    }

    public function beforeGetPluginCssAssets(Plugin $plugin): void
    {
        if (lx::$app->presetManager->isBuildType(PresetManager::BUILD_TYPE_NONE)) {
            return;
        }

        $cssCompiler = new CssAssetCompiler($plugin);
        $cssCompiler->compile();;
    }

    public function beforeGetAutoLinkPathes(array $originalPathes, array $linkPathes): void
    {
        $compiler = new AssetCompiler();
        $compiler->createLinks($originalPathes, $linkPathes);
    }
}
