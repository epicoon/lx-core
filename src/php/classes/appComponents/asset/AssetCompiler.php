<?php

namespace lx;

use lx;

class AssetCompiler
{
    public static function getLinksMap(array $map): array
    {
        $result = [
            'origins' => [],
            'links' => [],
            'names' => [],
        ];
        foreach ($map as $key => $value) {
            if (!preg_match('/^(\/web\/|http:|https:)/', $value)) {
                preg_match('/\.[^.\/]+$/', $value, $ext);
                $ext = $ext[0] ?? null;
                if ($ext == '.css') {
                    $parentDir = dirname($value);
                    $file = basename($value);
                    $path = '/web/auto/' . md5($parentDir);
                    $result['origins'][$key] = $parentDir;
                    $result['links'][$key] = $path;
                    $result['names'][$key] = $path . '/' . $file;
                } elseif ($ext) {
                    $path = '/web/auto/' . md5($value);
                    $result['origins'][$key] = $value;
                    $result['links'][$key] = $path . $ext;
                    $result['names'][$key] = $path . $ext;
                } else {
                    $path = '/web/auto/' . md5($value);
                    $result['origins'][$key] = $value;
                    $result['links'][$key] = $path;
                    $result['names'][$key] = $path;
                }
            } else {
                $result['names'][$key] = $value;
            }
        }

        return $result;
    }

    public function makePluginsAssetLinks(): void
    {
        $services = ServiceBrowser::getServicesList();
        foreach ($services as $service) {
            $plugins = $service->getStaticPlugins();
            foreach ($plugins as $plugin) {
                $origins = $plugin->getScriptsList();
                $arr = [];
                foreach ($origins as $value) {
                    $arr[] = $value['path'];
                }
                $linksMap = self::getLinksMap($arr);
                $this->createLinks($linksMap['origins'], $linksMap['links']);

                $origins = $plugin->getCssList();
                $linksMap = self::getLinksMap($origins);
                $this->createLinks($linksMap['origins'], $linksMap['links']);

                $origins = $plugin->getImagePathsList();
                $linksMap = self::getLinksMap($origins);
                $this->createLinks($linksMap['origins'], $linksMap['links']);
            }
        }
    }

    public function createLinks(array $originalPathes, array $linkPathes): void
    {
        $sitePath = lx::$conductor->sitePath;
        foreach ($linkPathes as $key => $linkPath) {
            $fullLinkPath = $sitePath . $linkPath;
            $fullOriginPath = $sitePath . $originalPathes[$key];
            if ($fullOriginPath == $fullLinkPath || !file_exists($fullOriginPath)) {
                continue;
            }

            $linkFile = new FileLink($fullLinkPath);
            if (!$linkFile->exists()) {
                $dir = $linkFile->getParentDir();
                $dir->make();
                $linkFile->create(BaseFile::construct($fullOriginPath));
            }
        }
    }

    public function copyLxCss(): void
    {
        $coreCssPath = lx::$conductor->core . '/css';
        $dir = new \lx\Directory($coreCssPath);

        $webCssPath = lx::$conductor->webCss;
        $dir->clone($webCssPath);
    }

    public function compileLxCss(): void
    {
        $path = lx::$conductor->webCss;
        $cssJsFile = new File($path . '/main.css.js');
        if (!$cssJsFile->exists()) {
            $this->copyLxCss();
        }

        $presets = lx::$app->presetManager->getCssPresets();
        $need = [];
        $cssFile = new File($path . "/main.css");
        if (!$cssFile->exists() || $cssJsFile->isNewer($cssFile)) {
            $need['__main__'] = $cssFile;
        }
        foreach ($presets as $name => $module) {
            $cssFile = new File($path . "/main-{$name}.css");
            if (!$cssFile->exists() || $cssJsFile->isNewer($cssFile)) {
                $need[$name] = $cssFile;
            }
        }
        if (empty($need)) {
            return;
        }

        $code = $cssJsFile->get();
        $names = [];
        $modules = [];
        foreach (lx::$app->presetManager->getCssPresets() as $type => $preset) {
            $code .= '#lx:use ' . lx::$app->presetManager->getCssPresetModule($type) . ';';
            $names[] = "'{$type}'";
        }
        $code .= "
            const list = [" . implode(',', $names) . "];
            const map = {};
            for (let i in list) {
                let name = list[i];
                const asset = new lx.CssContext();
                asset.usePreset(lx.CssPresetsList.getCssPreset(name));
                initCss(asset);
                map[name] = asset.toString();
            }
            return map;
        ";

        $compiler = new JsCompiler();
        $compiler->setBuildModules(true);
        $exec = new NodeJsExecutor($compiler);
        $map = $exec->setCore([
            '-R @core/js/commonCore',
            '-R @core/js/common/tools/',
            '-R @core/js/server/app/classes/',
            '-R @core/js/server/tools/',
        ])->setPath($cssJsFile->getPath())
            ->setCode($code)
            ->run();

        foreach ($need as $name => $file) {
            if ($file->getName() == 'main.css') {
                $file->put(self::getCommonCss($map));
            } else {
                $file->put($map[$name]);
            }
        }
    }
    
    public static function getCommonCss(array $cssList, ?Plugin $context = null): string
    {
        $map = [];
        foreach ($cssList as $type => $code) {
            if ($code == '') {
                continue;
            }

            preg_match_all('/([^}]+?)(?P<therec>{((?>[^{}]+)|(?P>therec))*})/', $code, $matches);
            $map[$type] = [];
            foreach ($matches[1] as $i => $key) {
                $map[$type][$key] = $matches['therec'][$i];
            }
        }
        if (empty($map)) {
            return '';
        }

        $common = [];
        foreach ($map as $type => $list) {
            foreach ($list as $rule => $values) {
                if (array_key_exists($rule, $common) && $common[$rule] != $values) {
                    \lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
                        '__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
                        'msg' => 'Css compiling mismatch' . ($context ? " for plugin {$context->name}" : ''),
                    ]);
                    continue;
                }

                $common[$rule] = $values;
            }
        }

        $commonCssCode = '';
        foreach ($common as $rule => $values) {
            $commonCssCode .= $rule . $values;
        }
        return $commonCssCode;
    }

    public function compileJsCore(): string
    {
        $path = lx::$conductor->jsClientCore;
        $code = file_get_contents($path);

        $jsCompiler = new JsCompiler();
        $code = $jsCompiler->compileCode($code, $path);

        $servicesList = ServiceBrowser::getServicesList();
        $modules = [];
        foreach ($servicesList as $service) {
            $modules = array_merge($modules, $service->getJsModules());
        }
        if (!empty($modules)) {
            $modulesProvider = new JsModuleProvider();
            $code .= $modulesProvider->getModulesCode($modules);
        }

        if (lx::$app->language) {
            $code .= 'lx.lang=' . CodeConverterHelper::arrayToJsCode(lx::$app->language->getCurrentData()) . ';';
        }

        return $code;
    }
}
