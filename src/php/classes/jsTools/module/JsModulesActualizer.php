<?php

namespace lx;

use lx;

class JsModulesActualizer
{
    /**
     * Renew application list of services which has modules
     */
    public function renewHead(): void
    {
        $list = ServiceBrowser::getServicePathesList();
        $names = [];
        foreach (array_keys($list) as $serviceName) {
            $service = lx::$app->getService($serviceName);
            if (!$service) {
                continue;
            }

            $mapFile = lx::$app->jsModules->conductor->getServiceMapFile($service);
            if ($mapFile->exists()) {
                $names[] = $serviceName;
            }
        }

        $file = lx::$app->jsModules->conductor->getHeadListFile();
        $file->put($names);
    }

    public function renewProjectServices(): void
    {
        $list = ServiceBrowser::getServicesList();
        $services = [];
        foreach ($list as $service) {
            if ($service->isProjectCategory()) {
                $services[] = $service;
            }
        }
        $this->renewServices($services);
    }

    /**
     * Renew modules meta-data in all services
     */
    public function renewAllServices(): void
    {
        $this->renewServices(ServiceBrowser::getServicesList());
    }

    /**
     * @param array<Service> $services
     */
    public function renewServices(array $services): void
    {
        $refreshedNames = [];
        $deprecatedNames = [];

        foreach ($services as $service) {
            $this->serviceRenewProcess($service)
                ? $refreshedNames[] = $service->name
                : $deprecatedNames[] = $service->name;
        }

        $file = lx::$app->jsModules->conductor->getHeadListFile();
        $list = $file->exists() ? $file->get() : [];
        $list = array_diff($list, $deprecatedNames);
        $list = array_unique(array_merge($list, $refreshedNames));
        $file->put($list);
    }

    /**
     * Renew modules meta-data in the service
     */
    public function renewService(Service $service): Service
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
        $mapFile = lx::$app->jsModules->conductor->getServiceMapFile($service);

        $map = $this->makeMap($service->directory);
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
        $files = $dir->getAllFiles(['mask' => '*.js']);
        $map = [];
        $files->each(function ($file) use ($dir, &$map) {
            $code = $file->get();
            preg_match('/(?<!\/ )(?<!\/)#lx:module\s+([^;]+?);/', $code, $matches);
            if (empty($matches)) {
                return;
            }

            $moduleName = $matches[1];
            if ($moduleName[0] == '<') {
                return;
            }

            //TODO нужна компиляция, список определенных классов, их расширения, зависимости от других модулей...
            $info = [
                'name' => $moduleName,
                'path' => $file->getRelativePath($dir->getPath()),
                //classes
            ];

            $moduleData = $this->readModuleData($code);
            if (!empty($moduleData)) {
                $info['data'] = $moduleData;
            }

            $map[$moduleName] = $info;
        });

        return $map;
    }

    private function readModuleData(string $code): array
    {
        $reg = '/#lx:module-data\s*(?P<therec>{((?>[^{}]+)|(?P>therec))*})/';
        preg_match_all($reg, $code, $matches);
        if (empty($matches[0])) {
            return [];
        }

        $dataStr = trim($matches['therec'][0], '{}');
        $dataStr = preg_replace('/(^[\s\r\n]+|[\s\r\n]+$)/', '', $dataStr);
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
        $path = lx::$conductor->getSystemPath('jsModulesMap.json');
        $file = lx::$app->diProcessor->createByInterface(DataFileInterface::class, [$path]);
        $data = $file->exists() ? $file->get() : [];

        if (!in_array($serviceName, $data)) {
            $data[] = $serviceName;
        }

        $file->put($data);
    }

    private function delService(string $serviceName): void
    {
        $path = lx::$conductor->getSystemPath('jsModulesMap.json');
        $file = lx::$app->diProcessor->createByInterface(DataFileInterface::class, [$path]);
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
