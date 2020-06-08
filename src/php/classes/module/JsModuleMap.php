<?php

namespace lx;

/**
 * Class JsModuleMap
 * @package lx
 */
class JsModuleMap
{
	/** @var array */
	private static $map;

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
	 * @return array
	 */
	public function getMap()
	{
		if (!self::$map) {
		    $app = \lx::$app;
			$path = $app->conductor->getFullPath('@core/jsModulesMap.json');
			$file = $app->diProcessor->createByInterface(DataFileInterface::class, [$path]);
			$result = $file->get();

			$path = \lx::$conductor->getSystemPath('jsModulesMap.json');
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

				$result = array_merge($result, $serviceFile->get());
			}

			self::$map = $result;
		}


		return self::$map;
	}
}
