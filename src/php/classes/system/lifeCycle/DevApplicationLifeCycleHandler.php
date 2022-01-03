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
        $css = $plugin->getConfig('css');
        if (!$css) {
            return;
        }

        $css = (array)$css;
        $cssCompiler = new AssetCompiler();
        foreach ($css as $value) {
            $path = $plugin->conductor->getFullPath($value);
            $cssCompiler->compileCssInDirectory($plugin, $path);
        }
    }

    public function beforeReturnAutoLinkPathes(array $originalPathes, array $linkPathes): void
    {
        $compiler = new AssetCompiler();
        $compiler->createLinks($originalPathes, $linkPathes);
    }
}
