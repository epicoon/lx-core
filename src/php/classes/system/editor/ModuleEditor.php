<?php

namespace lx;

class ModuleEditor {
	private $service;

	public function __construct($service) {
		$this->service = $service;
	}

	/**
	 *
	 * */
	public function createModule($name, $path) {
		$servicePath = $this->service->getPath();
		$moduleRootPath = $path == ''
			? $servicePath
			: $servicePath . '/' . $path;
		$fullPath = $moduleRootPath . '/' . $name;

		if (file_exists($fullPath)) {
			throw new \Exception("Directory '$fullPath' already exists", 400);
			return;
		}

		require( __DIR__ . '/moduleTpl.php' );
		/**
		 * @var string $respondentCode
		 * @var string $bootstrapJsCode
		 * @var string $mainJsCode
		 * @var string $viewCode
		 * @var string $moduleCode
		 * */

		// Определим пространство имен
		$namespace = '';
		$psr = $this->service->getConfig('autoload.psr-4');
		if (!$psr) {
			$namespace = $name . '\\';
		} else {
			foreach ($psr as $prefix => $path) {
				$rPath = $path == '' ? $servicePath : $servicePath . '/' . $path;
				if (strpos($fullPath, $rPath) == 0) {
					$subPath = explode($rPath . '/', $fullPath)[1];
					$namespace = $prefix . str_replace('/', '\\', $subPath);
					break;
				}
			}
		}

		$d = (new Directory($moduleRootPath))->makeDirectory($name);
		$moduleConfig = \lx::getDefaultModuleConfig();

		$backendName = $moduleConfig['respondents'];
		//todo - может же быть и массивом?
		$backend = $d->makeDirectory($backendName);
		$respondent = $backend->makeFile('Respondent.php');
		$respondentCode = str_replace('namespace ', 'namespace ' . $namespace . '\\'. $backendName, $respondentCode);
		$respondent->put($respondentCode);

		$frontend = $d->makeDirectory($moduleConfig['frontend']);
		$mainJs = $frontend->makeFile($moduleConfig['jsMain']);
		$mainJs->put($mainJsCode);
		$bootstrapJs = $frontend->makeFile($moduleConfig['jsBootstrap']);
		$bootstrapJs->put($bootstrapJsCode);

		$view = $d->makeDirectory($moduleConfig['view']);
		$root = $view->makeFile($moduleConfig['viewIndex']);
		$root->put($viewCode);

		$module = $d->makeFile('Module.php');
		$moduleCode = str_replace('namespace ', 'namespace ' . $namespace, $moduleCode);
		$module->put($moduleCode);

		$config = $d->makeFile('lx-config.yaml');
		$text = 'class: ' . $namespace . '\\Module' . chr(10) . chr(10);
		$text .= 'view: ' . $moduleConfig['view'] . chr(10);
		$text .= 'viewIndex: ' . $moduleConfig['viewIndex'] . chr(10) . chr(10);
		$text .= 'frontend: ' . $moduleConfig['frontend'] . chr(10);
		$text .= 'jsMain: ' . $moduleConfig['jsMain'] . chr(10);
		$text .= 'jsBootstrap: ' . $moduleConfig['jsBootstrap'] . chr(10) . chr(10);
		$text .= 'respondents: ' . $moduleConfig['respondents'] . chr(10);
		$config->put($text);

		return $d;
	}
}
