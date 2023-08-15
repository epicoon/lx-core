<?php

namespace lx;

use lx;

class PluginEditor
{
	private Service $service;
    private bool $config = true;
    private bool $respondent = true;
    private bool $client = true;
    private bool $snippets = true;

	public function __construct(Service $service)
	{
		$this->service = $service;
	}

    public function buildConfig(bool $value): PluginEditor
    {
        $this->config = $value;
        return $this;
    }

    public function buildRespondent(bool $value): PluginEditor
    {
        $this->respondent = $value;
        return $this;
    }

    public function buildClient(bool $value): PluginEditor
    {
        $this->client = $value;
        return $this;
    }

    public function buildSnippets(bool $value): PluginEditor
    {
        $this->snippets = $value;
        return $this;
    }

	public function createPlugin(string $name, ?string $path = null, array $config = []): ?Plugin
	{
		$servicePath = $this->service->getPath();
		if ($path === null) {
		    $pluginPathes = $this->service->getConfig('plugins');
		    if (is_array($pluginPathes)) {
		        $path = $pluginPathes[0];
            } else {
		        $path = $pluginPathes;
            }
        }
		$pluginRootPath = ($path == '')
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

        /**
         * @var string $respondentCode
         * @var string $mainJsCode
         * @var string $viewCode
         * @var string $pluginCode
         */
		require(__DIR__ . '/pluginTpl.php');

		$namespace = '';
		$psr = $this->service->getConfig('autoload.psr-4');
		if (!$psr) {
			$namespace = $name . '\\';
		} else {
			foreach ($psr as $prefix => $path) {
				foreach ((array)$path as $pathI) {
					$rPath = $pathI == '' ? $servicePath : $servicePath . '/' . $pathI;
					if (strpos($fullPath, $rPath) === 0) {
						$subPath = explode($rPath . '/', $fullPath)[1];
						$namespace = $prefix . str_replace('/', '\\', $subPath);
						break 2;
					}
				}
			}
		}
        $namespace .= '\\server';

		$d = (new Directory($pluginRootPath))->makeDirectory($name);
		$pluginConfig = \lx::$app->getDefaultPluginConfig();

        if ($this->client) {
            $mainJs = $d->makeFile($pluginConfig['client']);
            $mainJs->put($mainJsCode);
        }

        if ($this->snippets) {
            $root = $d->makeFile($pluginConfig['rootSnippet']);
            $root->put($viewCode);
        }

		$plugin = $d->makeFile('server/Plugin.php');
		$pluginCode = str_replace('namespace ', 'namespace ' . $namespace, $pluginCode);
		$plugin->put($pluginCode);

        if ($this->config) {
            $configFile = $d->makeFile('lx-config.yaml');
            $text = 'server: ' . $namespace . '\\Plugin' . PHP_EOL;
            $text .= 'client: ' . $pluginConfig['client'] . PHP_EOL . PHP_EOL;

            $text .= 'snippets: ' . $pluginConfig['snippets'] . PHP_EOL;
            $text .= 'rootSnippet: ' . $pluginConfig['rootSnippet'] . PHP_EOL . PHP_EOL;

            if ($this->respondent) {
                if (is_array($pluginConfig['respondents'])) {
                    if (empty($pluginConfig['respondents'])) {
                        $text .= 'respondents: {}'. PHP_EOL . PHP_EOL;
                    } else {
                        $text .= $this->createRespondents(
                                $pluginConfig['respondents'],
                                $d->get('server'),
                                $namespace,
                                $respondentCode
                            ) . PHP_EOL . PHP_EOL;
                    }
                } else {
                    $text .= 'respondents: ' . $pluginConfig['respondents'] . chr(10);
                }
            }

            $text .= 'images: ' . $pluginConfig['images'] . PHP_EOL . PHP_EOL;

            $text .= 'cacheType: ' . ($config['cacheType'] ?? $pluginConfig['cacheType'] ?? lx\Plugin::CACHE_NONE);

            $configFile->put($text);
        }

		return Plugin::create($this->service, $name, $fullPath);
	}

	private function createRespondents(
	    array $respondents,
        Directory $pluginDir,
        string $pluginNamespace,
        string $code
    ): string
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
