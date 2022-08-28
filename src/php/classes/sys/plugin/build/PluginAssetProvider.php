<?php

namespace lx;

use lx;

class PluginAssetProvider
{
    const EVENT_BEFORE_GET_AUTO_LINKS = 'pluginEventBeforeGetAutoLinks';
    const EVENT_BEFORE_GET_CSS_ASSETS = 'pluginEventBeforeGetCssAssets';

    private Plugin $plugin;

    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;
    }

    public function getPlugin(): Plugin
    {
        return $this->plugin;
    }

    public function getPluginScripts(): array
    {
        $list = $this->getPlugin()->getScriptsList();
        $arr = [];
        foreach ($list as $script) {
            $arr[] = $script->getPath();
        }

        $linksMap = AssetCompiler::getLinksMap($arr);
        lx::$app->events->trigger(self::EVENT_BEFORE_GET_AUTO_LINKS, [
            $linksMap['origins'],
            $linksMap['links']
        ]);

        foreach ($linksMap['names'] as $key => $name) {
            $list[$key]->setPath($name);
        }

        return $list;
    }

    public function getPluginCss(): array
    {
        if (lx::$app->cssManager->isBuildType(CssManager::BUILD_TYPE_NONE)) {
            return [];
        }

        $plugin = $this->getPlugin();
        lx::$app->events->trigger(self::EVENT_BEFORE_GET_CSS_ASSETS, $plugin);

        $originCss = $plugin->getCssList();
        $list = [];
        foreach ($originCss as $path) {
            if (lx::$app->cssManager->isBuildType(CssManager::BUILD_TYPE_SEGREGATED)) {
                if (basename($path) == 'asset.css') {
                    continue;
                }
            } elseif (lx::$app->cssManager->isBuildType(CssManager::BUILD_TYPE_ALL_TOGETHER)) {
                if (basename($path) != 'asset.css') {
                    continue;
                }
            }
            $list[] = $path;
        }
        $linksMap = AssetCompiler::getLinksMap($list);
        lx::$app->events->trigger(self::EVENT_BEFORE_GET_AUTO_LINKS, [
            $linksMap['origins'],
            $linksMap['links']
        ]);

        return $linksMap['names'];
    }

    public function getImagePaths(): array
    {
        $list = $this->getPlugin()->getImagePathsList();
        $linksMap = AssetCompiler::getLinksMap($list);
        lx::$app->events->trigger(self::EVENT_BEFORE_GET_AUTO_LINKS, [
            $linksMap['origins'],
            $linksMap['links']
        ]);
        return $linksMap['names'];
    }
}
