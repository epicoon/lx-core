<?php

namespace lx;

use lx;

class ModuleCssCompiler
{
    public function compile(array $moduleNames): void
    {
        $modulesInfo = lx::$app->jsModules->getModulesInfo($moduleNames);
        $deprecatedCssFiles = $this->getDeprecatedModuleNames($modulesInfo);
        if (empty($deprecatedCssFiles)) {
            return;
        }

        $code = $this->getCode(array_keys($deprecatedCssFiles));
        $compiler = new JsCompiler();
        $compiler->setBuildModules(true);
        $exec = new NodeJsExecutor($compiler);
        $result = $exec
            ->configureApplication()
            ->setCode($code)
            ->run();

        /** @var File $cssFile */
        foreach ($deprecatedCssFiles as $moduleName => $cssFile) {
            $cssList = $result[$moduleName];
            $presetedClasses = $cssList['presetedClasses'];
            unset($cssList['presetedClasses']);

            $dir = $cssFile->getParentDir();
            $presetedFile = new File($dir->getPath() . '/preseted.json');
            $presetedFile->put(json_encode($presetedClasses));

            $cssFile->put(AppAssetCompiler::getCommonCss($cssList));
            foreach ($cssList as $presetName => $css) {
                $cssPresetedFile = new File($dir->getPath() . "/asset-{$presetName}.css");
                $cssPresetedFile->put($css);
            }
        }
    }

    private function getCode(array $moduleNames): string
    {
        $contexts = lx::$app->cssManager->getCssContexts();
        $presets = lx::$app->cssManager->getCssPresets();

        $code = '';
        foreach ($contexts as $context) {
            $code .= '#lx:use ' . $context . ';';
        }
        $modules = [];
        foreach ($moduleNames as $moduleName) {
            $code .= '#lx:use ' . $moduleName . ';';
            $modules[] = "'$moduleName'";
        }
        foreach ($presets as $type => $preset) {
            $code .= '#lx:use ' . $preset . ';';
        }
        $code .= 'return lx.app.cssManager.renderModuleCss([' . implode(',', $modules) . ']);';
        return $code;
    }

    private function getDeprecatedModuleNames(array $modulesInfo): array
    {
        $sysDir = new Directory(lx::$conductor->getSystemPath('modules'));
        if (!$sysDir->exists()) {
            $sysDir->make();
        }

        $deprecated = [];
        /** @var JsModuleInfo $info */
        foreach ($modulesInfo as $name => $info) {
            $srcFile = $info->getSrcFile();
            $code = $srcFile->get();
            if (!preg_match('/static\s+initCss\s*\([^)]+?\)\s*\{/', $code)) {
                continue;
            }

            $cssFile = new File($sysDir->getPath() . '/' . $name . '/asset.css');
            if (!$cssFile->exists()) {
                $deprecated[$name] = $cssFile;
                continue;
            }

            if ($srcFile->isNewer($cssFile)) {
                $deprecated[$name] = $cssFile;
                continue;
            }

            $dir = $cssFile->getParentDir();
            foreach (lx::$app->cssManager->getCssPresets() as $name => $preset) {
                $file = new File($dir->getPath() . "/asset-{$name}.css");
                if (!$file->exists()) {
                    $deprecated[$name] = $cssFile;
                    break;
                }
            }
        }

        return $deprecated;
    }
}
