<?php

namespace lx;

class JsModuleMapBuilder
{
    /**
     * Renew application list of services which has modules
     */
    public function renewHead(): void
    {
        $list = PackageBrowser::getServicePathesList();
        $names = [];
        foreach (array_keys($list) as $serviceName) {
            $service = \lx::$app->getService($serviceName);
            if (!$service) {
                continue;
            }

            $modulesDirectory = $service->conductor->getModuleMapDirectory();
            $mapFile = new File($modulesDirectory->getPath() . '/jsModulesMap.json');
            if ($mapFile->exists()) {
                $names[] = $serviceName;
            }
        }

        $path = \lx::$conductor->getSystemPath('jsModulesMap.json');
        $file = \lx::$app->diProcessor->createByInterface(DataFileInterface::class, [$path]);
        $file->put($names);
    }

    /**
     * Renew modules meta-data in all services
     */
    public function renewAllServices(): void
    {
        $list = PackageBrowser::getServicePathesList();
        $names = [];
        foreach (array_keys($list) as $serviceName) {
            $service = \lx::$app->getService($serviceName);
            if (!$service) {
                continue;
            }

            if ($this->serviceRenewProcess($service)) {
                $names[] = $serviceName;
            }
        }

        $path = \lx::$conductor->getSystemPath('jsModulesMap.json');
        $file = \lx::$app->diProcessor->createByInterface(DataFileInterface::class, [$path]);
        $file->put($names);
    }

    /**
     * Renew modules meta-data in the service
     */
    public function renewService($service): Service
    {
        if ($this->serviceRenewProcess($service)) {
            $this->addService($service->name);
        } else {
            $this->delService($service->name);
        }
    }

    /**
     * Renew modules meta-data for core
     */
    public function renewCore(): void
    {
        $dir = new Directory('@core');
        $map = $this->makeMap($dir);
        $mapFile = $dir->makeFile('jsModulesMap.json', DataFileInterface::class);
        $mapFile->put($map);
    }


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * PRIVATE
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    private function serviceRenewProcess(Service $service): bool
    {
        $map = $this->makeMap($service->directory);
        $modulesDirectory = $service->conductor->getModuleMapDirectory();
        $mapFile = $modulesDirectory->makeFile('jsModulesMap.json', DataFileInterface::class);
        if (empty($map)) {
            if ($mapFile->exists()) {
                $mapFile->remove();
            }
            return false;
        }

        $mapFile->put($map);
        return true;
    }

    private function makeMap(DirectoryInterface $dir): array
    {
        $files = $dir->getAllFiles('*.js');
        $map = [];
        $files->each(function ($file) use ($dir, &$map) {
            $code = $file->get();
            preg_match('/(?<!\/ )(?<!\/)#lx:module\s+([^;]+?);/', $code, $matches);
            if (empty($matches)) {
                return;
            }

            //TODO нужна компиляция, список определенных классов, их расширения, зависимости от других модулей...
            $info = [
                'name' => $matches[1],
                'path' => $file->getRelativePath($dir->getPath()),
                //classes
            ];

            $moduleData = $this->readModuleData($code);
            if (!empty($moduleData)) {
                $info['data'] = $moduleData;
            }

            $map[$matches[1]] = $info;
        });

        return $map;
    }

    private function readModuleData(string $code): array
    {
        $reg = '/#lx:module-data\s+{([^}]*?)}/';
        preg_match_all($reg, $code, $matches);
        if (empty($matches[0])) {
            return [];
        }

        $dataStr = $matches[1][0];
        $dataArr = preg_split('/\s*,\s*/', $dataStr);
        $result = [];
        foreach ($dataArr as $item) {
            preg_match_all('/\s*([\w\W]+?)\s*:\s*([\w\W]+)/', $item, $matches);
            if (empty($matches[0])) {
                continue;
            }

            $result[$matches[1][0]] = trim($matches[2][0]);
        }

        return $result;
    }

    private function addService(string $serviceName): void
    {
        $path = \lx::$conductor->getSystemPath('jsModulesMap.json');
        $file = \lx::$app->diProcessor->createByInterface(DataFileInterface::class, [$path]);
        $data = $file->exists() ? $file->get() : [];

        if (!in_array($serviceName, $data)) {
            $data[] = $serviceName;
        }

        $file->put($data);
    }

    private function delService(string $serviceName): void
    {
        $path = \lx::$conductor->getSystemPath('jsModulesMap.json');
        $file = \lx::$app->diProcessor->createByInterface(DataFileInterface::class, [$path]);
        if (!$file->exists()) {
            return;
        }

        $data = $file->get();
        if (($key = array_search($serviceName, $data)) !== false) {
            unset($data[$key]);
            $file->put($data);
        }
    }
}
