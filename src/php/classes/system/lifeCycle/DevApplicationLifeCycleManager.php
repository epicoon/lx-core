<?php

namespace lx;

use lx;

class DevApplicationLifeCycleManager implements ApplicationLifeCycleManagerInterface, FusionComponentInterface
{
    use ApplicationToolTrait;
    use FusionComponentTrait;

    /**
     * Method called as the first step in [[lx::$app->run()]]
     */
    public function beforeRun(): void
    {
        $compiler = new AssetCompiler();

        $compiler->compileLxCss();

        $coreCode = $compiler->compileJsCore();
        $file = new File(lx::$conductor->webJs . '/core.js');
        $file->put($coreCode);
    }

    /**
     * Method called as last step in [[lx::$app->run()]]
     */
    public function afterRun(): void
    {
        // pass
    }

    /**
     * Method called as the first step in [[lx\PluginConductor::getCssAssets()]]
     */
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
            $cssCompiler->compileCssInDirectory($path);
        }
    }

    /**
     * Method called as last step in:
     * - [[lx\Plugin::getScripts()]]
     * - [[lx\Plugin::getCss()]]
     * - [[lx\Plugin::getImagePathes()]]
     */
    public function beforeReturnAutoLinkPathes(array $originalPathes, array $linkPathes): void
    {
        $compiler = new AssetCompiler();
        $compiler->createLinks($originalPathes, $linkPathes);
    }
}
