<?php

namespace lx;

class ServiceEditor {
	public function createService($name, $path) {
		$serviceRootPath = \lx::$app->conductor->getFullPath($path, \lx::$app->sitePath);
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

		$serviceConfig = \lx::$app->getDefaultServiceConfig();
		$pluginsDirName = $serviceConfig['plugins'];
		$modelsDirName = $serviceConfig['models'];

		$namespace = $name;
		preg_match_all('/^([^\/]+?)\//', $namespace, $matches);
		if (!empty($matches[1])) {
			$vendor = $matches[1][0];
			$namespace = preg_replace('/^([^\/]+?\/)'. $vendor .'-/', '$1', $namespace);
		}
		$namespace = str_replace('-', '', ucwords($namespace, '-'));
		$namespace = lcfirst($namespace);
		$namespace = str_replace('/', '\\', $namespace);
		$serviceName = $namespace . '\\Service';
		$arr = explode('/', $name);
		$route = array_pop($arr);

		$serviceDir = (new Directory($serviceRootPath))->makeDirectory($name, 0777, true);

		$configCode = str_replace('<name>', $name, $configCode);
		$configCode = str_replace('<nmsp>', $namespace . '\\', $configCode);
		$configCode = str_replace('<service>', $serviceName, $configCode);
		$configCode = str_replace('<route>', $route, $configCode);
		$configCode = str_replace('<plugin>', $pluginsDirName, $configCode);
		$configCode = str_replace('<model>', $modelsDirName, $configCode);
		$config = $serviceDir->makeFile('lx-config.yaml');
		$config->put($configCode);

		$serviceCode = str_replace('namespace ', 'namespace ' . $namespace, $serviceCode);
		$serviceFile = $serviceDir->makeFile('Service.php');
		$serviceFile->put($serviceCode);

		$serviceDir->makeDirectory($pluginsDirName);
		$serviceDir->makeDirectory($modelsDirName);

		(new AutoloadMapBuilder())->createCommonAutoloadMap();
		Autoloader::getInstance()->map->reset();
		return \lx::$app->getService($name);
	}
}
