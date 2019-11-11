<?php

namespace lx;

class JsModuleMapBuilder extends ApplicationTool {
	public function renewHead() {
		$list = PackageBrowser::getServiceNamesList();
		$names = [];
		foreach (array_keys($list) as $serviceName) {
			$service = $this->app->getService($serviceName);
			if (!$service) {
				continue;
			}

			$modulesDirectory = $service->conductor->getModuleMapDirectory();
			$mapFile = new File($modulesDirectory->getPath() . '/jsModulesMap.json');
			if ($mapFile->exists()) {
				$names[] = $serviceName;
			}
		}

		$path = \lx::$conductor->getSystemPath('systemPath') . '/jsModulesMap.json';
		$file = new ConfigFile($path);
		$file->put($names);
	}

	public function renewCore() {
		$dir = new Directory($this->app->conductor->getFullPath('@core'));
		$map = $this->makeMap($dir);
		$mapFile = $dir->makeFile('jsModulesMap.json', ConfigFile::class);
		$mapFile->put($map);
	}

	public function renewAllServices() {
		$list = PackageBrowser::getServiceNamesList();
		$names = [];
		foreach (array_keys($list) as $serviceName) {
			$service = $this->app->getService($serviceName);
			if (!$service) {
				continue;
			}

			if ($this->serviceRenewProcess($service)) {
				$names[] = $serviceName;
			}
		}

		$path = \lx::$conductor->getSystemPath('systemPath') . '/jsModulesMap.json';
		$file = new ConfigFile($path);
		$file->put($names);
	}

	/**
	 * @param $service
	 */
	public function renewService($service) {
		if ($this->serviceRenewProcess($service)) {
			$this->addService($service->name);
		} else {
			$this->delService($service->name);
		}
	}

	/**
	 * @param $service
	 * @return bool
	 */
	private function serviceRenewProcess($service) {
		$map = $this->makeMap($service->directory);
		$modulesDirectory = $service->conductor->getModuleMapDirectory();
		$mapFile = $modulesDirectory->makeFile('jsModulesMap.json', ConfigFile::class);
		if (empty($map)) {
			if ($mapFile->exists()) {
				$mapFile->remove();
			}
			return false;
		}

		$mapFile->put($map);
		return true;
	}

	/**
	 * @param $dir
	 * @return array
	 */
	private function makeMap($dir) {
		$files = $dir->getAllFiles('*.js');
		$map = [];
		$sitePath = $this->app->sitePath;
		$files->each(function($file) use ($sitePath, &$map) {
			$code = $file->get();
			preg_match('/(?<!\/ )(?<!\/)#lx:module\s+([^;]+?);/', $code, $matches);
			if (empty($matches)) {
				return;
			}

			//TODO нужна компиляция, список определенных классов, их расширения, зависимости от других модулей...
			$info = [
				'name' => $matches[1],
				'path' => $file->getRelativePath($sitePath),
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

	/**
	 * @param $code
	 * @return array
	 */
	private function readModuleData($code) {
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

	/**
	 * @param $serviceName
	 */
	private function addService($serviceName) {
		$path = \lx::$conductor->getSystemPath('systemPath') . '/jsModulesMap.json';
		$file = new ConfigFile($path);
		$data = $file->exists() ? $file->get() : [];

		if (!in_array($serviceName, $data)) {
			$data[] = $serviceName;
		}

		$file->put($data);
	}

	/**
	 * @param $serviceName
	 */
	private function delService($serviceName) {
		$path = \lx::$conductor->getSystemPath('systemPath') . '/jsModulesMap.json';
		$file = new ConfigFile($path);
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
