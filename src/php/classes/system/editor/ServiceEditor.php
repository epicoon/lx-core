<?php

namespace lx;

/**
 * Class ServiceEditor
 * @package lx
 */
class ServiceEditor
{
	/**
	 * @param string $name
	 * @param string $path
	 * @return Service|null
	 */
	public function createService($name, $path)
	{
		$serviceRootPath = \lx::$app->conductor->getFullPath($path);
		$fullPath = $serviceRootPath . '/' . $name;

		if (file_exists($fullPath)) {
			\lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
				'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
				'msg' => "Directory '$fullPath' already exists",
			]);
			return null;
		}

		require(__DIR__ . '/serviceTpl.php');
		/**
		 * @var string $configCode
		 * @var string $serviceCode
		 */

		$serviceConfig = \lx::$app->getDefaultServiceConfig();
        $pluginsDirName = $serviceConfig['plugins'] ?? 'plugins';

		$namespace = $this->defineNamespace($name);
		$serviceName = $namespace . '\\Service';

		$serviceDir = (new Directory($serviceRootPath))->makeDirectory($name, 0777, true);

		$configCode = str_replace('<name>', $name, $configCode);
		$configCode = str_replace('<nmsp>', $namespace . '\\', $configCode);
		$configCode = str_replace('<service>', $serviceName, $configCode);
		$configCode = str_replace('<plugin>', $pluginsDirName, $configCode);
		$config = $serviceDir->makeFile('lx-config.yaml');
		$config->put($configCode);

		$serviceCode = str_replace('namespace ', 'namespace ' . $namespace, $serviceCode);
		$serviceFile = $serviceDir->makeFile('Service.php');
		$serviceFile->put($serviceCode);

		$serviceDir->makeDirectory($pluginsDirName);

		(new AutoloadMapBuilder())->createCommonAutoloadMap();
		Autoloader::getInstance()->map->reset();
		return \lx::$app->getService($name);
	}

	/**
	 * @param string $name
	 * @return string
	 */
	private function defineNamespace($name)
	{
		$namespace = $name;
		preg_match_all('/^([^\/]+?)\//', $namespace, $matches);
		if (!empty($matches[1])) {
			$vendor = $matches[1][0];
			$namespace = preg_replace('/^([^\/]+?\/)' . $vendor . '-/', '$1', $namespace);
		}
		$namespace = str_replace('-', '', ucwords($namespace, '-'));
		$namespace = lcfirst($namespace);
		$namespace = str_replace('/', '\\', $namespace);
		return $namespace;
	}
}
