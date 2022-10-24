<?php

namespace lx;

use lx;

class PluginCssCompiler
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
        if (lx::$app->cssManager->isBuildType(CssManager::BUILD_TYPE_NONE)) {
            return;
        }

        $this->force = $force;
        $needMap = $this->getNeedMap();
        $needFiles = $this->getNeedFiles($needMap);
        $code = $this->getCode();
        if ($code == '') {
            $this->clearFiles($needFiles);
            return;
        }

        $plugin = $this->plugin;
        $compiler = new PluginFrontendJsCompiler($plugin);
        $compiler->setBuildModules(true);
        $exec = new NodeJsExecutor($compiler);
        $result = $exec
            ->configureApplication()
            ->setCode($code)
            ->setPath($plugin->conductor->getFullPath($plugin->getConfig('client')))
            ->run();
        $css = $result['css'];
        $presetedClasses = $result['presetedClasses'];

        $cssDir = new Directory($plugin->conductor->getLocalSystemPath('css'));
        $presetedFile = $cssDir->makeFile('preseted.json');
        $presetedFile->put(json_encode($presetedClasses));

        foreach ($needFiles as $type => $file) {
            if ($type == '__common__') {
                $this->compileCommonFile($file, $css);
            } else {
                $this->compileFile($file, $type, $css);
            }
        }
    }

    private function compileCommonFile(FileInterface $file, array $cssList): void
    {
        $cssCode = AppAssetCompiler::getCommonCss($cssList, $this->plugin->name);
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

    private function getCode(): string
    {
        $plugin = $this->plugin;

        $initCssContextCode = '';
        $pluginFile = new File($plugin->conductor->getFullPath($plugin->getConfig('client')));
        $pluginCode = $pluginFile->get();
        $reg = '/(initCss\([^\)]+?\))\s*(?P<therec>{((?>[^{}]+)|(?P>therec))*})/';
        preg_match_all($reg, $pluginCode, $matches);
        if (!empty($matches['therec'])) {
            $cssContext = trim($matches['therec'][0], '{} ');
            $cssContext = preg_replace('(^\s+|\s+$)', '', $cssContext);
            $initCssContextCode = ($cssContext == '') ? '' : ($matches[1][0] . '{' . $cssContext . '}');
        }

        $getCssContextClassesCode = '';
        $cssAssets = $plugin->getConfig('cssAssets');
        if ($cssAssets) {
            $assetClasses = implode(',', $cssAssets);
            $getCssContextClassesCode .= "getCssAssetClasses(){return [$assetClasses];}";
        }

        if ($getCssContextClassesCode == '' && $initCssContextCode == '') {
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
        $contexts = lx::$app->cssManager->getCssContexts();
        foreach ($contexts as $context) {
            $code .= '#lx:use ' . $context . ';';
        }
        foreach (lx::$app->cssManager->getCssPresets() as $preset) {
            $code .= '#lx:use ' . $preset . ';';
        }
        $code .= 'const __plugin__ = (()=>{class Plugin extends lx.Plugin{'
            . "init(){this._cssPreset='$type';}"
            . $getCssContextClassesCode
            . $initCssContextCode
            . '}return new Plugin('
            . CodeConverterHelper::arrayToJsCode($plugin->getBuildData())
            . ');})();';

        $code .= '
            const result = {};
            const proxyList = [' . implode(',', $contexts) . '];
            let proxies = [];
            proxyList.forEach(proxy=>proxies.push(new proxy()));
        ';
        foreach (lx::$app->cssManager->getCssPresets() as $type => $preset) {
            $code .= 'var context = new lx.CssContext();'
                . 'context.configure({'
                    . 'proxyContexts: proxies,'
                    . 'preset: lx.app.cssManager.getPreset(\'' . $type . '\')'
                .'});'
                . '__plugin__.initCss(context);'
                . 'result.' . $type . '= context.toString();';
        }
        $code .= 'return {css: result, presetedClasses: context.presetedClasses};';
        return $code;
    }

    private function getNeedFiles(array $needMap): array
    {
        $plugin = $this->plugin;
        $cssFiles = $plugin->getCssList();
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
        $cssManager = lx::$app->cssManager;
        $needMap = ['asset.css' => ['type' => '__common__', 'need' => true, 'file' => null]];
        if ($cssManager->isBuildType(CssManager::BUILD_TYPE_SEGREGATED)) {
            foreach ($cssManager->getCssPresets() as $name => $preset) {
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
