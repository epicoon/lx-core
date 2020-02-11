<?php

namespace lx;

class JsModuleMap extends Object {
	use ApplicationToolTrait;

	private static $map;

	public function getModuleInfo($moduleName)
	{
		$map = $this->getMap();
		return $map[$moduleName] ?? null;
	}

	public function getMap() {
		if (!self::$map) {
			$path = $this->app->conductor->getFullPath('@core/jsModulesMap.json');
			$file = new ConfigFile($path);
			$result = $file->get();

			$path = \lx::$conductor->getSystemPath('systemPath') . '/jsModulesMap.json';
			$file = new ConfigFile($path);
			$list = $file->get();
			foreach ($list as $serviceName) {
				$service = $this->app->getService($serviceName);
				if (!$service) {
					continue;
				}

				$serviceFile = new ConfigFile(
					$service->conductor->getModuleMapDirectory()->getPath()
					. '/jsModulesMap.json'
				);
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
