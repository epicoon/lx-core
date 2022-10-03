<?php

namespace lx;

use lx;

class JsModulesComponent implements FusionComponentInterface
{
    use FusionComponentTrait;

    const EVENT_BEFORE_COMPILE_MODULE_CODE = 'beforeCompileModuleCode';
    const EVENT_BEFORE_GET_AUTO_LINKS = 'moduleEventBeforeGetAutoLinks';
    const EVENT_BEFORE_GET_CSS_ASSETS = 'moduleEventBeforeGetCssAssets';

    private ?array $map = null;
    private array $list = [];

    public function reset(): void
    {
        $this->map = null;
        $this->list = [];
    }
    
    public function getMap(): array
    {
        if ($this->map === null) {
            $this->map = $this->loadMap();
        }
        
        return $this->map;
    }
    
    public function isModuleExist(string $moduleName): bool
    {
        return array_key_exists($moduleName, $this->getMap());
    }

    public function getModuleInfo(string $moduleName): ?JsModuleInfo
    {
        if (!array_key_exists($moduleName, $this->list) && $this->isModuleExist($moduleName)) {
            $map = $this->getMap();
            $this->list[$moduleName] = new JsModuleInfo($moduleName, $map[$moduleName]);
        }
        
        return $this->list[$moduleName] ?? null;
    }

    public function getModulesInfo(?array $modulesName = null): iterable
    {
        if ($modulesName === null) {
            $modulesName = array_keys($this->getMap());
        }
        
        $result = [];
        foreach ($modulesName as $name) {
            $result[$name] = $this->getModuleInfo($name);
        }
        return $result;
    }

    public function getModulesCss(array $moduleNames): array
    {
        if (empty($moduleNames) || lx::$app->cssManager->isBuildType(CssManager::BUILD_TYPE_NONE)) {
            return [];
        }

        lx::$app->events->trigger(JsModulesComponent::EVENT_BEFORE_GET_CSS_ASSETS, [
            'moduleNames' => $moduleNames,
        ]);

        $isSegregated = lx::$app->cssManager->isBuildType(CssManager::BUILD_TYPE_SEGREGATED);
        $presets = $isSegregated ? lx::$app->cssManager->getCssPresets() : [];

        $list = [];
        foreach ($moduleNames as $moduleName) {
            $sysDir = new Directory(lx::$conductor->getSystemPath('modules/' . $moduleName));
            if (!$sysDir->exists()) {
                continue;
            }

            if ($isSegregated) {
                foreach ($presets as $presetName => $preset) {
                    $list[] = $sysDir->getRelativePath(lx::$app->sitePath) . "/asset-{$presetName}.css";
                }
            } else {
                $list[] = $sysDir->getRelativePath(lx::$app->sitePath) . '/asset.css';
            }
        }

        $linksMap = WebAssetHelper::getLinksMap($list);
        if (!empty($linksMap['origins'])) {
            lx::$app->events->trigger(self::EVENT_BEFORE_GET_AUTO_LINKS, [
                'origins' => $linksMap['origins'],
                'links' => $linksMap['links'],
            ]);
        }

        return $linksMap['names'];
    }

    public function getCoreModules(): array
    {
        $modules = [];
        lx::$app->eachFusionComponent(function($component, $name) use (&$modules) {
            if ($component instanceof JsModuleClientInterface) {
                $modules = array_merge($modules, $component->getJsModules());
            }
        });
        return $modules;
    }

    public function getPresetedCssClasses(array $moduleNames): array
    {
        $list = [];
        foreach ($moduleNames as $moduleName) {
            $sysDir = new Directory(lx::$conductor->getSystemPath('modules/' . $moduleName));
            if (!$sysDir->exists()) {
                continue;
            }

            $file = $sysDir->get('preseted.json');
            if (!$file) {
                continue;
            }

            $list = array_merge($list, json_decode($file->get(), this));
        }
        return $list;
    }

    private function loadMap(): array
    {
        $app = lx::$app;
        $path = $app->conductor->getFullPath('@core/jsModulesMap.json');
        $file = $app->diProcessor->createByInterface(DataFileInterface::class, [$path]);
        $result = $file->get();
        foreach ($result as &$item) {
            $item['service'] = 'lx/core';
            $item['path'] = lx::$app->conductor->getFullPath('@core') . '/' . $item['path'];
        }
        unset($item);

        $path = lx::$conductor->getSystemPath('jsModulesMap.json');
        $file = $app->diProcessor->createByInterface(DataFileInterface::class, [$path]);
        $list = $file->get();
        foreach ($list as $serviceName) {
            $service = $app->getService($serviceName);
            if (!$service) {
                continue;
            }

            $name = $service->conductor->getModuleMapDirectory()->getPath() . '/jsModulesMap.json';
            $serviceFile = $app->diProcessor->createByInterface(DataFileInterface::class, [$name]);
            if (!$serviceFile) {
                continue;
            }

            $info = $serviceFile->get();
            foreach ($info as &$item) {
                $item['service'] = $service->name;
                $item['path'] = $service->getPath() . '/' . $item['path'];
            }
            unset($item);
            $result = array_merge($result, $info);
        }

        return $result;
    }
}
