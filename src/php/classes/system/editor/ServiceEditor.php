<?php

namespace lx;

class ServiceEditor {
	public function createService($name, $path) {
		$serviceRootPath = \lx::$conductor->getFullPath($path, \lx::sitePath());
		$fullPath = $serviceRootPath . '/' . $name;

		if (file_exists($fullPath)) {
			throw new \Exception("Directory '$fullPath' already exists", 400);
			return;
		}

		require( __DIR__ . '/serviceTpl.php' );
		/**
		 * @var string $configCode
		 * @var string $serviceCode
		 * */

		$serviceConfig = \lx::getDefaultServiceConfig();
		$modulesDirName = $serviceConfig['modules'];
		$modelsDirName = $serviceConfig['models'];

		$namespace = str_replace('/', '\\', $name);
		$serviceName = $namespace . '\\Service';
		$arr = explode('/', $name);
		$route = array_pop($arr);

		$serviceDir = (new Directory($serviceRootPath))->makeDirectory($name, 0777, true);

		$configCode = str_replace('<name>', $name, $configCode);
		$configCode = str_replace('<nmsp>', $namespace . '\\', $configCode);
		$configCode = str_replace('<service>', $serviceName, $configCode);
		$configCode = str_replace('<route>', $route, $configCode);
		$configCode = str_replace('<module>', $modulesDirName, $configCode);
		$configCode = str_replace('<model>', $modelsDirName, $configCode);
		$config = $serviceDir->makeFile('lx-config.yaml');
		$config->put($configCode);

		$serviceCode = str_replace('namespace ', 'namespace ' . $namespace, $serviceCode);
		$serviceFile = $serviceDir->makeFile('Service.php');
		$serviceFile->put($serviceCode);

		$serviceDir->makeDirectory($modulesDirName);
		$serviceDir->makeDirectory($modelsDirName);

		(new AutoloadMapBuilder())->createCommonAutoloadMap();
		Autoloader::getInstance()->map->reset();
		return Service::create($name);
	}
}
