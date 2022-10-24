<?php

namespace lx;

use lx;

class MainCssCompiler
{
    public function compile(): void
    {
        if (!$this->requireUpdate()) {
            return;
        }

        $code = $this->getCode();
        $compiler = new JsCompiler();
        $exec = new NodeJsExecutor($compiler);
        $result = $exec
            ->configureApplication()
            ->setCode($code)
            ->run();

        $presetedClasses = $result['presetedClasses'];
        $presetedFile = AppAssetCompiler::getAppPresetedFile();
        $presetedFile->put(json_encode($presetedClasses));

        $path = lx::$conductor->webLx;
        $map = $result['map'];
        $need = ['__main__' => new File($path . '/main.css')];
        if (lx::$app->cssManager->isBuildType(CssManager::BUILD_TYPE_SEGREGATED)) {
            foreach (lx::$app->cssManager->getCssPresets() as $name => $module) {
                $need[$name] = new File($path . "/main-{$name}.css");
            }
        }
        foreach ($need as $name => $file) {
            if ($file->getName() == 'main.css') {
                $file->put(AppAssetCompiler::getCommonCss($map));
            } else {
                $file->put($map[$name]);
            }
        }
    }

    private function getCode(): string
    {
        $contexts = lx::$app->cssManager->getCssContexts();
        $assets = lx::$app->cssManager->getCssAssets();
        $presets = lx::$app->cssManager->getCssPresets();

        $code = '';
        $names = [];
        foreach ($contexts as $context) {
            $code .= '#lx:use ' . $context . ';';
        }
        foreach ($assets as $asset) {
            $code .= '#lx:use ' . $asset . ';';
        }
        foreach ($presets as $type => $preset) {
            $code .= '#lx:use ' . $preset . ';';
            $names[] = "'{$type}'";
        }
        $code .= '
            const presetNamesList = [' . implode(',', $names) . '];
            const proxyList = [' . implode(',', $contexts) . '];
            const assetsList = [' . implode(',', $assets) . '];
            let proxies = [];
            proxyList.forEach(proxy=>proxies.push(new proxy()));
            const map = {};
            const presetedClasses = [];
            presetNamesList.forEach(name=>map[name]="");
            assetsList.forEach(assetClass=>{
                const asset = new assetClass();
                asset.useContexts(proxies);
                presetNamesList.forEach(name=>{
                    const preset = lx.app.cssManager.getPreset(name);
                    asset.usePreset(preset);
                    asset.prepare();
                    asset.init(preset);
                    map[name] += asset.toString();
                });
                asset.presetedClasses.forEach(name=>presetedClasses.push(name));
            });
            return {map, presetedClasses};
        ';
        return $code;
    }

    private function requireUpdate(): bool
    {
        $commonFile = new File(lx::$conductor->webLx . '/main.css');
        if (!$commonFile->exists()) {
            return true;
        }

        if (lx::$app->cssManager->isBuildType(CssManager::BUILD_TYPE_SEGREGATED)) {
            foreach (lx::$app->cssManager->getCssPresets() as $name => $preset) {
                $file = new File(lx::$conductor->webLx . "/main-{$name}.css");
                if (!$file->exists()) {
                    return true;
                }
            }
        }

        $contexts = lx::$app->cssManager->getCssContexts();
        $assets = lx::$app->cssManager->getCssAssets();
        $files = [];
        /** @var JsModuleInfo $info */
        foreach (lx::$app->jsModules->getModulesInfo($contexts) as $info) {
            $files[] = new File($info->getPath());
        }
        foreach (lx::$app->jsModules->getModulesInfo($assets) as $info) {
            $files[] = new File($info->getPath());
        }

        foreach ($files as $file) {
            if ($file->isNewer($commonFile)) {
                return true;
            }
        }

        return false;
    }
}
