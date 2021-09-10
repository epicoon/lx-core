<?php

namespace lx;

use lx;

class JsModuleMap
{
	private static ?array $map = null;

	public function moduleExists(string $moduleName): bool
    {
        $map = $this->getMap();
        return array_key_exists($moduleName, $map);
    }

	public function getModuleInfo(string $moduleName): ?array
	{
		$map = $this->getMap();
		return $map[$moduleName] ?? null;
	}

	public function getModuleData(string $moduleName): array
    {
        $map = $this->getMap();
        $info = $map[$moduleName] ?? [];
        return $info['data'] ?? [];
    }

    public function getModulePath(string $moduleName): ?string
    {
        if (!$this->moduleExists($moduleName)) {
            return null;
        }

        $map = $this->getMap();
        $info = $map[$moduleName];
        return $info['path'];
    }

    public function getModuleService(string $moduleName): ?Service
    {
        if (!$this->moduleExists($moduleName)) {
            return null;
        }

        $map = $this->getMap();
        $info = $map[$moduleName];
        return lx::$app->getService($info['service']);
    }

	public function getMap(bool $reload = false): array
	{
	    if ($reload) {
	        self::$map = null;
        }
	    
		if (!self::$map) {
		    $app = lx::$app;
			$path = $app->conductor->getFullPath('@core/jsModulesMap.json');
			$file = $app->diProcessor->createByInterface(DataFileInterface::class, [$path]);
            $info = $file->get();
            foreach ($info as &$item) {
                $item['service'] = 'lx/core';
                $item['path'] = lx::$app->conductor->getFullPath('@core') . '/' . $item['path'];
            }
            unset($item);
			$result = $info;

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
                    $item['service'] = 'lx/core';
                    $item['path'] = $service->getPath() . '/' . $item['path'];
                }
                unset($item);
				$result = array_merge($result, $info);
			}

			self::$map = $result;
		}


		return self::$map;
	}
}
