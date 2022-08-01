<?php

namespace lx;

use lx;

class CssAssetCompiler
{
    private Plugin $plugin;
    private bool $force = false;
    private ?int $pluginLastUpdate = null;

    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;
    }

    public function compile(bool $force = false): void
    {
        if (lx::$app->presetManager->isBuildType(PresetManager::BUILD_TYPE_NONE)) {
            return;
        }

        $this->force = $force;
        $needMap = $this->getNeedMap();
        $needFiles = $this->getNeedFiles($needMap);
        $code = $this->getCode($needFiles);
        if ($code == '') {
            $this->clearFiles($needFiles);
            return;
        }

        $plugin = $this->plugin;
        $compiler = new PluginFrontendJsCompiler($plugin);
        $compiler->setBuildModules(true);
        $exec = new NodeJsExecutor($compiler);
        $result = $exec
            ->setCore([
                '-R @core/js/commonCore',
                '-R @core/js/common/tools/',
                '-R @core/js/serverCore',
                '-R @core/js/client/app/classes/',
                '-R @core/js/server/tools/',
            ])
            ->setCode($code)
            ->setPath($plugin->conductor->getFullPath($plugin->getConfig('client')))
            ->run();

        foreach ($needFiles as $type => $file) {
            if ($type == '__common__') {
                $this->compileCommonFile($file, $result);
            } else {
                $this->compileFile($file, $type, $result);
            }
        }
    }

    private function compileCommonFile(FileInterface $file, array $cssList): void
    {
        $cssCode = AssetCompiler::getCommonCss($cssList, $this->plugin);
        $file->put($cssCode);
    }

    private function compileFile(FileInterface $file, string $type, array $result): void
    {
        if (!array_key_exists($type, $result)) {
            \lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
                '__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
                'msg' => "Css compiling problem with preset {$type} for plugin {$this->plugin->name}",
            ]);
            return;
        }

        if ($result[$type] == '') {
            return;
        }

        $file->put($result[$type]);
    }

    private function clearFiles(array $files): void
    {
        /** @var FileInterface $file */
        foreach ($files as $file) {
            if ($file->exists()) {
                $file->remove();
            }
        }
    }

    private function getCode(array $needFiles): string
    {
        $plugin = $this->plugin;

        $initCssAssetCode = '';
        $pluginFile = new File($plugin->conductor->getFullPath($plugin->getConfig('client')));
        $pluginCode = $pluginFile->get();
        $reg = '/(initCssAsset\([^\)]+?\))\s*(?P<therec>{((?>[^{}]+)|(?P>therec))*})/';
        preg_match_all($reg, $pluginCode, $matches);
        if (!empty($matches['therec'])) {
            $cssAsset = trim($matches['therec'][0], '{} ');
            $cssAsset = preg_replace('(^\s+|\s+$)', '', $cssAsset);
            $initCssAssetCode = ($cssAsset == '') ? '' : ($matches[1][0] . '{' . $cssAsset . '}');
        }

        $getCssAssetClassesCode = '';
        $cssAssets = $plugin->getConfig('cssAssets');
        if ($cssAssets) {
            $assetClasses = implode(',', $cssAssets);
            $getCssAssetClassesCode .= "getCssAssetClasses(){return [$assetClasses];}";
        }

        if ($getCssAssetClassesCode == '' && $initCssAssetCode == '') {
            return '';
        }

        $require = $plugin->getConfig('require');
        $requireStr = '';
        if ($require) {
            foreach ($require as $item) {
                $requireStr .= "#lx:require $item;";
            }
        }

        $code = $requireStr;
        foreach (lx::$app->presetManager->getCssPresets() as $type => $preset) {
            $code .= '#lx:use ' . lx::$app->presetManager->getCssPresetModule($type) . ';';
        }
        $code .= 'const __plugin__ = (()=>{class Plugin extends lx.Plugin{'
            . $getCssAssetClassesCode
            . $initCssAssetCode
            . '}return new Plugin(' . CodeConverterHelper::arrayToJsCode($plugin->getBuildData()) . ');})();';

        $code .= 'const result = {};';
        foreach (lx::$app->presetManager->getCssPresets() as $type => $preset) {
            $code .= 'var asset = new lx.CssAsset();'
                . 'asset.usePreset(lx.CssPresetsList.getCssPreset(\'' . $type . '\'));'
                . '__plugin__.initCssAsset(asset);'
                . 'result.' . $type . '= asset.toString();';
        }
        $code .= 'return result;';
        return $code;
    }

    private function getNeedFiles(array $needMap): array
    {
        $plugin = $this->plugin;
        $cssFiles = $plugin->getOriginCss();
        foreach ($cssFiles as $cssPath) {
            $cssFile = $plugin->getFile($cssPath);
            $fileName = $cssFile->getName();
            //TODO пока концептуально неочевидно что делать с произвольными файлами в css-директориях
            if (!array_key_exists($fileName, $needMap)) {
                continue;
            }

            if ($this->checkFileNeedUpdate($cssFile)) {
                $needMap[$fileName]['file'] = $cssFile;
            } else {
                $needMap[$fileName]['need'] = false;
            }
        }

        $pluginSystemCssDirectory = new Directory($plugin->conductor->getLocalSystemPath('css'));
        $needFiles = [];
        foreach ($needMap as $fileName => $data) {
            if ($data['need'] === false) {
                continue;
            }

            if ($data['file'] !== null) {
                $needFiles[$data['type']] = $data['file'];
            } else {
                $needFiles[$data['type']] = $pluginSystemCssDirectory->makeFile($fileName);
            }
        }

        return $needFiles;
    }

    private function getNeedMap(): array
    {
        $presetManager = lx::$app->presetManager;
        $needMap = ['asset.css' => ['type' => '__common__', 'need' => true, 'file' => null]];
        if ($presetManager->isBuildType(PresetManager::BUILD_TYPE_SEGREGATED)) {
            foreach ($presetManager->getCssPresets() as $name => $preset) {
                $needMap["asset-$name.css"] = ['type' => $name, 'need' => true, 'file' => null];
            }
        }

        return $needMap;
    }

    private function getPluginLastUpdate(): int
    {
        if ($this->pluginLastUpdate === null) {
            $this->pluginLastUpdate = 0;

            //TODO возможно ли придумать что-то более тонкое для проверки, что нужно пересобрать css-файлы?
            /** @var CommonFileInterface $file */
            foreach ($this->plugin->directory->getAllFiles('*.js') as $file) {
                $updated = $file->updatedAt();
                if ($this->pluginLastUpdate < $updated) {
                    $this->pluginLastUpdate = $updated;
                }
            }
        }

        return $this->pluginLastUpdate;
    }

    private function checkFileNeedUpdate(FileInterface $file): bool
    {
        if ($this->force) {
            return true;
        }

        return $file->updatedAt() < $this->getPluginLastUpdate();
    }
}
