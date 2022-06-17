<?php

namespace lx;

use lx;

class JsModulesComponent implements FusionComponentInterface
{
    use FusionComponentTrait;

    private ?array $map = null;
    private array $list = [];

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
