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

		$mainJs = $d->makeFile($moduleConfig['jsMain']);
		$mainJs->put($mainJsCode);
		$bootstrapJs = $d->makeFile($moduleConfig['jsBootstrap']);
		$bootstrapJs->put($bootstrapJsCode);

		$root = $d->makeFile($moduleConfig['view']);
		$root->put($viewCode);

		$module = $d->makeFile('Module.php');
		$moduleCode = str_replace('namespace ', 'namespace ' . $namespace, $moduleCode);
		$module->put($moduleCode);

		$config = $d->makeFile('lx-config.yaml');
		$text = 'class: ' . $namespace . '\\Module' . PHP_EOL . PHP_EOL;
		$text .= 'view: ' . $moduleConfig['view'] . PHP_EOL . PHP_EOL;
		$text .= 'jsMain: ' . $moduleConfig['jsMain'] . PHP_EOL;
		$text .= 'jsBootstrap: ' . $moduleConfig['jsBootstrap'] . PHP_EOL . PHP_EOL;

		if (is_array($moduleConfig['respondents'])) {
			if (empty($moduleConfig['respondents'])) {
				$text .= 'respondents: {}';
			} else {
				$text .= $this->createRespondents($moduleConfig['respondents'], $d, $namespace, $respondentCode);
			}
		} else {
			$text .= 'respondents: ' . $moduleConfig['respondents'] . chr(10);
		}
		$config->put($text);

		return Module::create($this->service, $name, $fullPath);
	}

	/**
	 *
	 * */
	private function createRespondents($respondents, $moduleDir, $moduleNamespace, $code) {
		$text = 'respondents:';

		foreach ($respondents as $key => $respondent) {
			$arr = preg_split('/^(.*?)\\\([^\\\\' . ']+)$/', $respondent, 0, PREG_SPLIT_DELIM_CAPTURE);
			if (count($arr) == 1) {
				$namespace = '';
				$className = $arr[0];
			} else {
				$namespace = $arr[1];
				$className = $arr[2];
			}

			$fullNamespace = $namespace == ''
				? $moduleNamespace
				: $moduleNamespace . '\\' . $namespace;

			$path = str_replace('\\', '/', $namespace);
			$dir = $path == ''
				? $moduleDir
				: $moduleDir->makeDirectory($path);

			$file = $dir->makeFile($className . '.php');
			$respondentCode = str_replace('namespace', 'namespace ' . $fullNamespace, $code);
			$respondentCode = str_replace('RespondentName', $className, $respondentCode);
			$file->put($respondentCode);

			$text .= PHP_EOL . "  $key: $respondent";
		}

		return $text;
	}
}
