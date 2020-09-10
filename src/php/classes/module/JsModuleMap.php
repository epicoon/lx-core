<?php

namespace lx;

use lx;

/**
 * Class JsModuleMap
 * @package lx
 */
class JsModuleMap
{
	/** @var array */
	private static $map;

    /**
     * @param string s$moduleName
     * @return bool
     */
	public function moduleExists($moduleName)
    {
        $map = $this->getMap();
        return array_key_exists($moduleName, $map);
    }

	/**
	 * @param string $moduleName
	 * @return array|null
	 */
	public function getModuleInfo($moduleName)
	{
		$map = $this->getMap();
		return $map[$moduleName] ?? null;
	}

    /**
     * @param string s$moduleName
     * @return array
     */
	public function getModuleData($moduleName)
    {
        $map = $this->getMap();
        $info = $map[$moduleName] ?? [];
        return $info['data'] ?? [];
    }

    /**
     * @param string s$moduleName
     * @return string|false
     */
    public function getModulePath($moduleName)
    {
        if (!$this->moduleExists($moduleName)) {
            return false;
        }

        $map = $this->getMap();
        $info = $map[$moduleName];
        return $info['path'];
    }

    /**
     * @param string s$moduleName
     * @return Service|null
     */
    public function getModuleService($moduleName)
    {
        if (!$this->moduleExists($moduleName)) {
            return null;
        }

        $map = $this->getMap();
        $info = $map[$moduleName];
        return lx::$app->getService($info['service']);
    }

    /**
	 * @return array
	 */
	public function getMap()
	{
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
