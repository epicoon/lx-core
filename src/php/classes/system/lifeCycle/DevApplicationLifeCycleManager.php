<?php

namespace lx;

use lx;

class DevApplicationLifeCycleManager implements FusionComponentInterface, EventListenerInterface
{
    use FusionComponentTrait;
    use EventListenerTrait;

    public static function getEventHandlersMap(): array
    {
        return [
            AbstractApplication::EVENT_BEFORE_RUN => 'beforeApplicationRun',
            AbstractApplication::EVENT_AFTER_RUN => 'afterApplicationRun',
            Plugin::EVENT_BEFORE_GET_AUTO_LINKS => 'beforeReturnAutoLinkPathes',
            Plugin::EVENT_BEFORE_GET_CSS_ASSETS => 'beforeGetPluginCssAssets',
        ];
    }

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
            $cssCompiler->compileCssInDirectory($path);
        }
    }

    public function beforeReturnAutoLinkPathes(array $originalPathes, array $linkPathes): void
    {
        $compiler = new AssetCompiler();
        $compiler->createLinks($originalPathes, $linkPathes);
    }
}
