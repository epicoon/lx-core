<?php

namespace lx;

use lx;

class JsModulesConductor
{
    public function getCommonSystemDirectory(): Directory
    {
        return new Directory(lx::$conductor->getSystemPath('modules'));
    }

    public function getModuleSystemDirectory(string $moduleName): Directory
    {
        return new Directory(lx::$conductor->getSystemPath('modules/' . $moduleName));
    }

    public function getHeadListFile(): DataFileInterface
    {
        return lx::$app->diProcessor->createByInterface(
            DataFileInterface::class,
            [lx::$conductor->getSystemPath('jsModulesMap.json')]
        );
    }

    public function getServiceMapFile(Service $service): DataFileInterface
    {
        $modulesDirectory = $service->conductor->getModuleMapDirectory();
        return lx::$app->diProcessor->createByInterface(
            DataFileInterface::class,
            [$modulesDirectory->getPath() . '/jsModulesMap.json']
        );
    }
}
