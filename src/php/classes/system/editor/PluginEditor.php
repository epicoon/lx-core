<?php

namespace lx;

/**
 * Class PluginEditor
 * @package lx
 */
class PluginEditor
{
	/** @var Service */
	private $service;

	/**
	 * PluginEditor constructor.
	 * @param Service $service
	 */
	public function __construct($service)
	{
		$this->service = $service;
	}

	/**
	 * @param string $name
	 * @param string $path
	 * @param array $config
	 * @return Plugin|null
	 */
	public function createPlugin($name, $path = null, $config = [])
	{
		$servicePath = $this->service->getPath();
		if ($path === null) {
		    $plaginPathes = $this->service->getConfig('plugins');
		    if (is_array($plaginPathes)) {
		        $path = $plaginPathes[0];
            } else {
		        $path = $plaginPathes;
            }
        }
		$pluginRootPath = $path == ''
			? $servicePath
			: $servicePath . '/' . $path;
		$fullPath = $pluginRootPath . '/' . $name;

		if (file_exists($fullPath)) {
			\lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
				'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
				'msg' => "Directory '$fullPath' already exists",
			]);
			return null;
		}

		require(__DIR__ . '/pluginTpl.php');
		/**
		 * @var string $respondentCode
		 * @var string $bootstrapJsCode
		 * @var string $mainJsCode
		 * @var string $viewCode
		 * @var string $pluginCode
		 */

		$namespace = '';
		$psr = $this->service->getConfig('autoload.psr-4');
		if (!$psr) {
			$namespace = $name . '\\';
		} else {
			foreach ($psr as $prefix => $path) {
				foreach ((array)$path as $pathI) {
					$rPath = $pathI == '' ? $servicePath : $servicePath . '/' . $pathI;
					if (strpos($fullPath, $rPath) == 0) {
						$subPath = explode($rPath . '/', $fullPath)[1];
						$namespace = $prefix . str_replace('/', '\\', $subPath);
						break 2;
					}
				}
			}
		}

		$d = (new Directory($pluginRootPath))->makeDirectory($name);
		$pluginConfig = \lx::$app->getDefaultPluginConfig();

		$mainJs = $d->makeFile($pluginConfig['jsMain']);
		$mainJs->put($mainJsCode);
		$bootstrapJs = $d->makeFile($pluginConfig['jsBootstrap']);
		$bootstrapJs->put($bootstrapJsCode);

		$root = $d->makeFile($pluginConfig['rootSnippet']);
		$root->put($viewCode);

		$plugin = $d->makeFile('Plugin.php');
		$pluginCode = str_replace('namespace ', 'namespace ' . $namespace, $pluginCode);
		$plugin->put($pluginCode);

		$configFile = $d->makeFile('lx-config.yaml');
		$text = 'class: ' . $namespace . '\\Plugin' . PHP_EOL . PHP_EOL;

		$text .= 'rootSnippet: ' . $pluginConfig['rootSnippet'] . PHP_EOL;
		$text .= 'snippets: ' . $pluginConfig['snippets'] . PHP_EOL . PHP_EOL;

		$text .= 'jsMain: ' . $pluginConfig['jsMain'] . PHP_EOL;
		$text .= 'jsBootstrap: ' . $pluginConfig['jsBootstrap'] . PHP_EOL . PHP_EOL;

		$text .= 'images: ' . $pluginConfig['images'] . PHP_EOL;
		$text .= 'css: ' . $pluginConfig['css'] . PHP_EOL . PHP_EOL;

		$text .= 'cacheType: ' . ($config['cacheType'] ?? $pluginConfig['cacheType'] ?? lx\Plugin::CACHE_NONE)
			. PHP_EOL . PHP_EOL;

		if (is_array($pluginConfig['respondents'])) {
			if (empty($pluginConfig['respondents'])) {
				$text .= 'respondents: {}';
			} else {
				$text .= $this->createRespondents($pluginConfig['respondents'], $d, $namespace, $respondentCode);
			}
		} else {
			$text .= 'respondents: ' . $pluginConfig['respondents'] . chr(10);
		}
		$configFile->put($text);

		return Plugin::create($this->service, $name, $fullPath);
	}

	/**
	 * @param array $respondents
	 * @param Directory $pluginDir
	 * @param string $pluginNamespace
	 * @param string $code
	 * @return string
	 */
	private function createRespondents($respondents, $pluginDir, $pluginNamespace, $code)
	{
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
				? $pluginNamespace
				: $pluginNamespace . '\\' . $namespace;

			$path = str_replace('\\', '/', $namespace);
			$dir = $path == ''
				? $pluginDir
				: $pluginDir->makeDirectory($path);

			$file = $dir->makeFile($className . '.php');
			$respondentCode = str_replace('namespace', 'namespace ' . $fullNamespace, $code);
			$respondentCode = str_replace('RespondentName', $className, $respondentCode);
			$file->put($respondentCode);

			$text .= PHP_EOL . "  $key: $respondent";
		}

		return $text;
	}
}
