<?php

namespace lx;

use lx;

class CliProcessor
{
	const COMMAND_TYPE_COMMON = 5;
	const COMMAND_TYPE_CONSOLE = 10;
	const COMMAND_TYPE_WEB = 15;

    private ?CliCommandsList $_commandsList = null;
	private ?array $_servicesList = null;
	private ?int $currentCommandType = null;
	private ?Service $service = null;
	private ?Plugin $plugin = null;
	private CliArgumentsList $args;
	private array $consoleMap = [];
	private ?string $needParam = null;
	private array $params = [];
	private array $invalidParams = [];
	private bool $keepProcess = false;
	private array $data = [];

    public function resetData(): void
    {
        $this->currentCommandType = null;
        $this->consoleMap = [];
        $this->needParam = null;
        $this->params = [];
        $this->invalidParams = [];
        $this->keepProcess = false;
        $this->data = [];
    }

	private function getSelfCommands(): array
    {
        return [
            [
                'type' => self::COMMAND_TYPE_CONSOLE,
                'command' => ['\q'],
                'description' => 'Exit CLI mode',
            ],

            [
                'command' => ['\h', 'help'],
                'description' => 'Show available commands list',
                'arguments' => [
                    $this->initArgument([0])
                        ->setType(CliArgument::TYPE_STRING)
                        ->setDescription('Command name'),
                ],
                'handler' => 'showHelp',
            ],

            [
                'command' => ['\g', 'goto'],
                'description' => 'Enter into service or plugin',
                'arguments' => [
                    $this->initArgument(['index', 'i'])
                        ->setType(CliArgument::TYPE_INTEGER)
                        ->setDescription('Index of service due to list returned by command "services-list"'),
                    $this->initArgument(['service', 's'])
                        ->setType(CliArgument::TYPE_STRING)
                        ->setDescription('Service name'),
                    $this->initArgument(['plugin', 'p'])
                        ->setType(CliArgument::TYPE_STRING)
                        ->setDescription('Plugin name'),
                    $this->initArgument(0)
                        ->setType(CliArgument::TYPE_STRING)
                        ->setDescription('Service or plugin name'),
                ],
                'handler' => 'move',
            ],

            [
                'command' => ['\p', 'fullpath'],
                'description' => 'Show path to service or plugin directory',
                'arguments' => [
                    $this->initArgument(['service', 's'])
                        ->setType(CliArgument::TYPE_STRING)
                        ->setDescription('Service name'),
                    $this->initArgument(['plugin', 'p'])
                        ->setType(CliArgument::TYPE_STRING)
                        ->setDescription('Plugin name'),
                    $this->initArgument(0)
                        ->setType(CliArgument::TYPE_STRING)
                        ->setDescription('Service or plugin name'),
                ],
                'handler' => 'fullPath',
            ],

            [
                'command' => ['\sl', 'services-list'],
                'description' => 'Show services list',
                'handler' => 'showServices',
            ],

            [
                'command' => ['\pl', 'plugins-list'],
                'description' => 'Show plugins list',
                'arguments' => [
                    $this->initArgument(['service', 's', 0])
                        ->setType(CliArgument::TYPE_STRING)
                        ->setDescription('Service name'),
                ],
                'handler' => 'showPlugins',
            ],

            [
                'command' => ['\cs', 'create-service'],
                'description' => 'Create new service',
                'arguments' => [
                    $this->initArgument(['name', 'n', 0])
                        ->setType(CliArgument::TYPE_STRING)
                        ->setDescription('New service name'),
                ],
                'handler' => 'createService',
            ],

            [
                'command' => ['\cp', 'create-plugin'],
                'description' => 'Create new plugin',
                'arguments' => [
                    $this->initArgument(['name', 'n', 0])
                        ->setType(CliArgument::TYPE_STRING)
                        ->setDescription('New plugin name'),
                ],
                'handler' => 'createPlugin',
            ],

            [
                'command' => ['\amr', 'autoload-map-reset'],
                'description' => 'Reset autoload map',
                'handler' => 'resetAutoloadMap',
            ],

            [
                'command' => ['\amrjs', 'autoload-map-reset-js'],
                'description' => 'Reset js-modules autoload map',
                'arguments' => [
                    $this->initArgument(['mode', 'm'])
                        ->setEnum(['core', 'head', 'all'])
                        ->setDescription('Reset mode: "head" (reset common map) or "all" (reset services map)'),
                    $this->initArgument(['service', 's'])
                        ->setType(CliArgument::TYPE_STRING)
                        ->setDescription('Service name'),
                ],
                'handler' => 'resetJsAutoloadMap',
            ],

            [
                'command' => ['cache'],
                'description' => 'Reset plugins cache',
                'arguments' => [
                    $this->initArgument(['service', 's'])
                        ->setType(CliArgument::TYPE_STRING)
                        ->setDescription('Service name'),
                    $this->initArgument(['plugin', 'p'])
                        ->setType(CliArgument::TYPE_STRING)
                        ->setDescription('Plugin name'),
                    $this->initArgument(['build', 'b'])
                        ->setFlag()
                        ->setDescription('Flag to build cache for plugins without cache'),
                    $this->initArgument(['clear', 'c'])
                        ->setFlag()
                        ->setDescription('Flag to clear cache'),
                    $this->initArgument(['rebuild', 'r'])
                        ->setFlag()
                        ->setDescription('Flag to rebuild cache'),
                ],
                'handler' => 'cacheManage',
            ],
        ];
    }

    /**
     * @param string|int|array $key
     */
    public function initArgument($key = null): CliArgument
    {
        $arg = new CliArgument();
        if ($key !== null) {
            $arg->setKeys((array)$key);
        }

        return $arg;
    }
	
	public function getCommandsList(): CliCommandsList
	{
	    if (!$this->_commandsList) {
            $list = new CliCommandsList();

            $list->setCommands(array_merge(
                $this->getSelfCommands(),
                $this->loadCommandsExtensionList()
            ));

            $this->_commandsList = $list;
        }
	    
	    return $this->_commandsList;
	}

	public function handleCommand(
	    string $commandName,
        int $commandType,
        array $args,
        ?Service $service,
        ?Plugin $plugin
    ): array
	{
		$this->service = $service;
		$this->plugin = $plugin;
		$this->args = new CliArgumentsList($args);
		$this->consoleMap = [];
		$this->needParam = null;

		$this->currentCommandType = $commandType;
        try {
            $this->invokeCommand($commandName);
        } catch (\Throwable $exception) {
            $msg = $exception->getFile() . '::' . $exception->getLine() . ' - ' . $exception->getMessage();
            lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
                '__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
                'msg' => $msg,
            ]);
            $this->resetData();
            $this->outln($msg);
        }
		return $this->getResult();
	}

    /**
     * Method tries to complete part of entered command
     * If there are several due alternatives will be returned the closest common part and array of alternatives
     */
    public function autoCompleteCommand(string $command, CliCommandsList $commandsList): ?array
    {
        $commandArray = preg_split('/ +/', $command);
        $count = count($commandArray);
        if ($count == 1) {
            $base = '';
            $text = $command;
        } elseif ($count == 2) {
            $base = $commandArray[0] . ' ';
            if ($base != '\\h ' && $base != 'help ') {
                return null;
            }
            $text = $commandArray[1];
        } else {
            return null;
        }

        $len = mb_strlen($text);
        if ($len == 0 || $text[0] == '\\') {
            return null;
        }

        $matches = [];
        $names = $commandsList->getCommandNames();
        foreach ($names as $command) {
            if ($command != $text && preg_match('/^' . $text . '/', $command)) {
                $matches[] = $command;
            }
        }

        if (empty($matches)) {
            return null;
        }

        $commonPart = $text;
        $i = $len;
        while (true) {
            $latterMatch = true;
            if ($i >= mb_strlen($matches[0])) break;
            $latter = $matches[0][$i];
            foreach ($matches as $command) {
                if ($i >= mb_strlen($command)) break(2);
                if ($latter != $command[$i]) break(2);
            }
            $commonPart .= $latter;
            $i++;
        }

        return [
            'common' => $base . $commonPart,
            'matches' => $matches
        ];
    }

	public function getData(): array
	{
		return $this->data;
	}

	public function setData(array $data): void
	{
		$this->data = $data;
	}

	/**
	 * @param mixed $value
	 */
	public function addData(string $name, $value): void
	{
		$this->data[$name] = $value;
	}

	public function getServicesList(): array
	{
		if ($this->_servicesList === null) {
			$this->resetServicesList();
		}
		return $this->_servicesList;
	}

	public function getService(): ?Service
	{
		return $this->service;
	}

	public function getPlugin(): ?Plugin
	{
		return $this->plugin;
	}

	public function setParams(array $params): void
	{
		$this->params = $params;
	}

	/**
	 * @param mixed $value
	 */
	public function setParam(string $name, $value): void
	{
		$this->params[$name] = $value;
	}

	/**
	 * @return mixed
	 */
	public function getParam(string $name)
	{
		if (!array_key_exists($name, $this->params)) {
			return null;
		}

		return $this->params[$name];
	}

	public function hasParam(string $name): bool
    {
        return array_key_exists($name, $this->params);
    }

	public function getResult(): array
	{
		$result = [
			'output' => $this->consoleMap,
			'params' => $this->params,
			'invalidParams' => $this->invalidParams,
			'need' => $this->needParam,
			'keepProcess' => $this->keepProcess,
		];

		if (!empty($this->getData())) {
			$result['data'] = $this->getData();
		}

		return $result;
	}

	public function invalidateParam(string $name): void
	{
		unset($this->params[$name]);
		$this->invalidParams[] = $name;
		$this->keepProcess = true;
	}

	/**
	 * Method to be used by CLI executors to stop process
	 */
	public function done(): void
	{
		$this->params = [];
		$this->invalidParams = [];
		$this->keepProcess = false;
		$this->currentCommandType = null;
	}

	public function out(string $text, array $decor = []): void
	{
		$this->consoleMap[] = ['out', $text, $decor];
	}

	public function outln(string $text = '', array $decor = []): void
	{
		$this->consoleMap[] = ['outln', $text, $decor];
	}

	public function in(string $needParam, string $text, array $decor = [], $password = false): void
	{
		$this->needParam = $needParam;
		$this->consoleMap[] = ['in', $text, $decor, $password];
	}

    public function select(string $needParam, array $options, string $text, array $decor = []): void
    {
        $this->needParam = $needParam;
        $this->consoleMap[] = ['select', $options, $text, $decor];
    }

	/**
	 * @param string|int|array $key
     * @param mixed $default
	 * @return mixed
	 */
	public function getArg($key, $default = null)
	{
	    if ($this->args->has($key)) {
            return $this->args->get($key);
        }

	    return $default;
	}

    /**
     * Method parses command name and arguments from console input
     *
     * Example for arguments as enumeration:
     * lx-cli<app>: command arg1 arg2 "arg3 by several words"
     *
     * Example for arguments by keys:
     * lx-cli<app>: command -k=arg1 --key="arg2 by several words"
     */
    public static function parseInput(string $input): array
    {
        $arr = StringHelper::smartSplit($input, ['delimiter' => ' ', 'save' => ['[]', '"']]);
        $command = array_shift($arr);

        $position = 0;
        $argsMap = [];
        foreach ($arr as $item) {
            if ($item[0] == '-') {
                $pos = strpos($item, '=');
                if ($pos === false) {
                    $argsMap[trim($item, '-')] = true;
                    continue;
                }

                $key = trim(substr($item, 0, $pos), '-');
                $value = substr($item, $pos + 1, strlen($item));
                if ($value[0] == '[') {
                    $value = trim($value, '[]');
                    $value = StringHelper::smartSplit($value, ['delimiter' => ',', 'save' => '"']);
                    foreach ($value as &$i) {
                        $i = trim($i, ' ');
                    }
                    unset($i);
                } else {
                    $value = trim($value, '"');
                }
                $argsMap[$key] = $value;
                continue;
            }

            if ($item[0] == '[') {
                $item = trim($item, '[]');
                $item = StringHelper::smartSplit($item, ['delimiter' => ',', 'save' => '"']);
                foreach ($item as &$i) {
                    $i = trim($i, ' ');
                }
                unset($i);
            } else {
                $item = trim($item, '"');
            }
            $argsMap[$position++] = $item;
        }

        return [$command, $argsMap];
    }


	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * PRIVATE FOR CONSOLE COMMANDS
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	private function showHelp(): void
	{
	    if ($this->args->isEmpty()) {
            $list = $this->getCommandsList()->getSubList([
                self::COMMAND_TYPE_COMMON,
                $this->currentCommandType,
            ]);

            $arr = [];
            foreach ($list->getCommands() as $command) {
                $arr[] = [
                    $command->getDescription(),
                    implode(', ', $command->getNames())
                ];
            }

            $arr = Console::alignTable($arr, '.');
            foreach ($arr as $row) {
                $this->out($row[0] . ': ', ['decor' => 'b']);
                $this->outln($row[1]);
            }
        }

	    $commandName = $this->args->get(0);
	    if (!$commandName) {
	        return;
        }

	    $command = $this->getCommandsList()->getCommand($commandName);
	    if (!$command) {
	        $this->outln('Unknown command name - ' . $commandName);
	        return;
        }

	    $this->outln('Command information:', ['decor' => 'b']);
	    $this->outln('* Commands: ' . implode(', ', $command->getNames()));
	    $this->outln('* Description: ' . $command->getDescription());
	    $args = $command->getArguments();
	    if (!empty($args)) {
	        $this->outln('* Arguments:');
	        foreach ($args as $argument) {
                $keys = $argument->getKeys();
                $this->out('  - ' . implode(', ', $keys) . ': ');
                if ($argument->isMandatory()) {
                    $this->out('mandatory', ['decor' => 'b']);
                    $this->out(', ');
                }

	            $type = $argument->getType();
                if ($type == CliArgument::TYPE_ENUM) {
                    $this->out('enum - [' . implode(', ', $argument->getEnum()) . ']. ');
                } else {
                    $this->out('type - ' . $type . '. ');
                }

                $this->outln($argument->getDescription());
            }
        }
	}

	private function move(): void
	{
		// Return to application
		if ($this->args->isEmpty()) {
			$this->plugin = null;
			$this->service = null;
			return;
		}

		$index = $this->args->get('index');
		if ($index !== null) {
		    $index--;
			$services = $this->getServicesList();
			if ($index > count($services)) {
				$this->outln('Maximum allowable index is ' . count($services));
				return;
			}

            $name = array_keys($services)[$index];
            $this->service = lx::$app->getService($name);
			$this->plugin = null;
            return;
		}

        $name = $this->args->get(0);
        if (Service::exists($name)) {
            $this->service = lx::$app->getService($name);
            $this->plugin = null;
            return;
        }

        $plugin = lx::$app->getPlugin($name);
        if ($plugin) {
            $this->service = $plugin->getService();
            $this->plugin = $plugin;
            return;
        }

        $this->outln('Destination not found');
	}

	private function fullPath(): void
	{
		if ($this->args->isEmpty()) {
			if ($this->plugin) {
				$this->outln('Path: ' . $this->plugin->getPath());
			} elseif ($this->service) {
				$this->outln('Path: ' . $this->service->getPath());
			} else {
				$this->outln('Path: ' . lx::$app->sitePath);
			}
			return;
		}

		$name = null;

        $serviceName = $this->args->get('service');
        if ($serviceName !== null && Service::exists($serviceName)) {
            $name = $serviceName;
        }

        if ($name === null) {
            $pluginName = $this->args->get('plugin');
            if ($pluginName !== null) {
                $name = $pluginName;
            }
        }

        if ($name === null) {
            $name = $this->args->get(0);
        }

		if ($name) {
			if (preg_match('/:/', $name)) {
				try {
					$path = lx::$app->getPluginPath($name);
					if ($path === null) {
						throw new \Exception('', 400);
					}
					$this->outln("Plugin '$name' path: " . $path);
					return;
				} catch (\Exception $e) {
					$this->outln("Plugin '$name' not found");
					return;
				}
			} else {
				try {
					$service = lx::$app->getService($name);
					$this->outln("Service '$name' path: " . $service->getPath());
					return;
				} catch (\Exception $e) {
					$this->outln("Service '$name' not found");
					return;
				}
			}
		}

		$this->outln('Wrong entered parameters');
	}

	private function resetAutoloadMap(): void
	{
		(new AutoloadMapBuilder())->createCommonAutoloadMap();
		$this->outln('Done');
	}

	private function resetJsAutoloadMap(): void
	{
	    switch ($this->args->get('mode')) {
            case 'core':
                $this->outln('Creating core map...');
                (new JsModuleMapActualizer())->renewCore();
                $this->outln('Done');
                return;
            case 'head':
                $this->outln('Updating modules list...');
                (new JsModuleMapActualizer())->renewHead();
                $this->outln('Done');
                return;
            case 'all':
                $this->outln('Updating modules list...');
                (new JsModuleMapActualizer())->renewAllServices();
                $this->outln('Done');
                return;
        }

        $service = null;
        $serviceName = $this->args->get('service');
        if ($serviceName) {
            if (Service::exists($serviceName)) {
                $service = lx::$app->getService($serviceName);
            } else {
                $this->outln("Service '$name' not found");
                return;
            }
		}
		if ($service === null) {
			$service = $this->service;
		}

		if ($service === null) {
		    $this->outln('Choose service or reset mode');
		    return;
        }

		$this->outln('Creating map for service "' . $service->name . '"...');
		(new JsModuleMapActualizer())->renewService($service);
		$this->outln('Done');
	}

	private function showServices(): void
	{
		$temp = $this->getServicesList();
		$data = [];
		$counter = 0;
		foreach ($temp as $value) {
			$data[] = [
				'num' => '' . (++$counter) . '.',
				'name' => $value['name'],
				'path' => $value['path'],
			];
		}
		$data = Console::alignTable($data);

		$this->outln('Services list:', ['decor' => 'b']);
		$counter = 0;
		foreach ($data as $value) {
			$num = $value['num'];
			$name = $value['name'];
			$path = $value['path'];
			$this->out($num . ' ', ['decor' => 'b']);
			$this->out('Name: ', ['decor' => 'b']);
			$this->out($name . '  ');
			$this->out('Path: ', ['decor' => 'b']);
			$this->outln($path);
		}
	}

	private function showPlugins(): void
	{
		$service = null;
		$serviceName = $this->args->get('service');
		if ($serviceName) {
		    if (Service::exists($serviceName)) {
				$service = lx::$app->getService($serviceName);
			} else {
				$this->outln("Service '$name' not found");
				return;
			}
		}
		if ($service === null) {
			$service = $this->service;
		}

		if ($service === null) {
			$this->outln("Plugins belong to services. Input the service name or enter one of them");
			return;
		}

		$plugins = PluginBrowser::getPluginsDataMap($service);
		$dynamic = $plugins['dynamic'];
		$static = $plugins['static'];

		$this->outln('* * * Plugins for service "' . $service->name . '" * * *', ['decor' => 'b']);

		$this->out('Dynamic plugins:', ['decor' => 'b']);
		if (empty($dynamic)) {
			$this->outln(' NONE');
		} else {
			$this->outln();
			foreach ($dynamic as $name => $data) {
				$this->out('* ', ['decor' => 'b']);
				$this->outln($name);
			}
		}

		$this->out('Static plugins:', ['decor' => 'b']);
		if (empty($static)) {
			$this->outln(' NONE');
		} else {
			$this->outln();
			$data = [];
			foreach ($static as $name => $path) {
				$data[] = [
					'name' => $name,
					'path' => $path
				];
			}
			$data = Console::alignTable($data);
			foreach ($data as $value) {
				$name = $value['name'];
				$path = $value['path'];
				$this->out('* Name: ', ['decor' => 'b']);
				$this->out($name . '  ');
				$this->out('Path: ', ['decor' => 'b']);
				$this->outln($path);
			}
		}
	}

	private function createService(): void
	{
		$dirs = lx::$app->getConfig('packagesMap');
		if ($dirs) {
			$dirs = (array)$dirs;
		}

		if (!is_array($dirs) || empty($dirs)) {
			$this->outln("Application configuration 'packagesMap' not found");
			$this->done();
			return;
		}

		if (!$this->hasParam('name')) {
			$name = $this->args->get('name');
			if (!$name) {
				$this->in('name', 'You need enter new service name: ', ['decor' => 'b']);
				return;
			}
			$this->setParam('name', $name);
		}
		$name = $this->getParam('name');
		if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9-]*?(?:\/[a-zA-Z-])?[a-zA-Z0-9-]*$/', $name)) {
			$this->in(
				'name',
				'Entered service name is incorrect, enter correct name: ',
				['decor' => 'b']
			);
			return;
		}

		if (count($dirs) == 1) {
			$this->createServiceProcess($name, $dirs[0]);
			$this->done();
			return;
		}

		if (!$this->hasParam('index')) {
            $dirsList = [];
            foreach ($dirs as $dirPath) {
                $dirsList[] = $dirPath == '' ? '/' : $dirPath;
            }
            $this->select('index', $dirsList, 'Choose directory for new service:', ['decor' => 'b']);
			return;
		}
		$i = $this->getParam('index');

		if ($i === null) {
			$this->outln('Aborted');
			$this->done();
			return;
		}
		if (!is_numeric($i) || $i < 0 || $i > count($dirs) - 1) {
			$this->invalidateParam('index');
			return;
		}

		$this->createServiceProcess($name, $dirs[$i]);
		$this->done();
	}

	private function createPlugin(): void
	{
		if ($this->service === null) {
			$this->outln("Plugins are belonging to services. Enter the service");
			$this->done();
			return;
		}

		if (!$this->hasParam('name')) {
			$name = $this->args->get('name');
			if (!$name) {
				$this->in('name', 'You need enter new plugin name: ', ['decor' => 'b']);
				return;
			}
			$this->setParam('name', $name);
		}
		$name = $this->getParam('name');
		if (!preg_match('/^[a-zA-Z_][\w-]*$/', $name)) {
			$this->in(
				'name',
				'Entered plugin name is incorrect, enter correct name: ',
				['decor' => 'b']
			);
			return;
		}

		$pluginDirs = $this->service->getConfig('plugins');
		if ($pluginDirs) {
			$pluginDirs = (array)$pluginDirs;
		}
		if (!is_array($pluginDirs) || empty($pluginDirs)) {
			$this->outln("Service configuration 'plugins' not found");
			$this->done();
			return;
		}

		if (count($pluginDirs) == 1) {
			$path = $pluginDirs[0];
			$this->createPluginProcess($this->service, $name, $path);
			$this->done();
			return;
		}

		if (!$this->hasParam('index')) {
            $dirsList = [];
            foreach ($pluginDirs as $dirPath) {
                $dirsList[] = $dirPath == '' ? '/' : $dirPath;
            }
            $this->select(
                'index',
                $dirsList,
                'Choose directory for new plugin (relative to the service directory):',
                ['decor' => 'b']
            );
			return;
		}
		$i = $this->getParam('index');

		if ($i === null) {
			$this->outln('Aborted');
			$this->done();
			return;
		}
		if (!is_numeric($i) || $i < 0 || $i > count($pluginDirs) - 1) {
			$this->invalidateParam('index');
			return;
		}

		$this->createPluginProcess($this->service, $name, $pluginDirs[$i]);
		$this->done();
	}

	private function cacheManage(): void
    {
        $plugin = null;
        $pluginName = $this->args->get('plugin');
        if ($pluginName) {
            $plugin = lx::$app->getPlugin($pluginName);
        }
        if ($plugin === null) {
            $plugin = $this->plugin;
        }

        $plugins = null;
        if ($plugin) {
            $plugins = [$plugin->getService()->name => [$plugin]];
        } else {
            $service = null;
            $serviceName = $this->args->get('service');
            if ($serviceName) {
                if (Service::exists($serviceName)) {
                    $service = lx::$app->getService($serviceName);
                } else {
                    $this->outln("Service '$name' not found");
                    return;
                }
            }
            if ($service === null) {
                $service = $this->service;
            }

            if ($service) {
                $plugins = [$service->name => $service->getStaticPlugins()];
            }
        }

        if ($plugins === null) {
            $plugins = [];
            $servicesList = $this->getServicesList();
            foreach ($servicesList as $serviceName => $serviceData) {
                $service = $serviceData['object'];
                $plugins[$service->name] = $service->getStaticPlugins();
            }
        }

        if ($this->getArg('build', false)) {
            foreach ($plugins as $serviceName => $servicePlugins) {
                foreach ($servicePlugins as $plugin) {
                    foreach ($plugins as $serviceName => $servicePlugins) {
                        foreach ($servicePlugins as $plugin) {
                            if ($plugin->getConfig('cacheType') != Plugin::CACHE_NONE) {
                                $plugin->buildCache();
                            }
                        }
                    }
                }
            }
            $this->outln('done');
            return;
        }

        if ($this->getArg('clear', false)) {
            foreach ($plugins as $serviceName => $servicePlugins) {
                foreach ($servicePlugins as $plugin) {
                    $plugin->dropCache();
                }
            }
            $this->outln('done');
            return;
        }

        if ($this->getArg('rebuild', false)) {
            foreach ($plugins as $serviceName => $servicePlugins) {
                foreach ($servicePlugins as $plugin) {
                    $cacheType = $plugin->getConfig('cacheType');
                    if (!$cacheType || $cacheType == Plugin::CACHE_NONE) {
                        continue;
                    }
                    $plugin->renewCache();
                }
            }
            $this->outln('done');
            return;
        }

        // Показать текущее состояние кэша
        foreach ($plugins as $serviceName => $servicePlugins) {
            $this->outln('Service ' . $serviceName . ':' , ['decor' => 'b']);
            $data = [];
            foreach ($servicePlugins as $plugin) {
                $info = $plugin->getCacheInfo();
                $data[] = [
                    'name' => $plugin->name,
                    'type' => $info['type'],
                    'state' => $info['exists'] ? 'exists' : 'no',
                ];
            }
            $data = Console::alignTable($data);
            foreach ($data as $row) {
                $this->out('  * ' . $row['name'] . ': ');
                $this->out('type: ', ['decor' => 'b']);
                $this->out($row['type'] . '  ');
                $this->out('state: ', ['decor' => 'b']);
                $this->outln($row['state']);
            }
        }
    }


	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * PRIVATE FOR PROCESSOR INNER WORK
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	private function resetServicesList(): void
	{
		$services = PackageBrowser::getServicePathesList();
		$data = [];
		foreach ($services as $name => $path) {
			$data[$name] = [
				'name' => $name,
				'path' => $path,
				'object' => lx::$app->getService($name),
			];
		}
		uksort($data, 'strcasecmp');
		$this->_servicesList = $data;
	}

	private function createServiceProcess(string $name, string $path): void
	{
		$editor = new ServiceEditor();
		$service = $editor->createService($name, $path);
		if ($service) {
			$this->resetServicesList();
			$this->out('New service created in: ');
			$this->outln($service->getPath(), ['decor' => 'u']);
		} else {
			$this->outln('Service was not created');
		}
	}

	private function createPluginProcess(Service $service, string $name, string $path): void
	{
		$editor = new PluginEditor($service);

		$plugin = $editor->createPlugin($name, $path);
		if ($plugin) {
			$dir = $plugin->directory;
			$this->out('New plugin was created in: ');
			$this->outln($dir->getPath(), ['decor' => 'u']);
		} else {
			$this->outln('Plugin was not created');
		}
	}

	private function invokeCommand(string $commandName): void
	{
	    $commands = $this->getCommandsList();
	    $command = $commands->getCommand($commandName);

	    $errorReport = $command->validateInput($this->args);
	    if (!empty($errorReport)) {
	        foreach ($errorReport as $row) {
	            $this->outln($row);
            }

	        return;
        }

	    $executor = $command->getExecutor();
        if (is_string($executor)) {
            if (method_exists($this, $executor)) {
                $this->{$executor}();
            } elseif (ClassHelper::implements($executor, ServiceCliExecutorInterface::class)) {
                /** @var ServiceCliExecutorInterface $object */
                $object = new $executor();
                $object->setProcessor($this);
                $object->run();
            }
        } elseif (
            is_array($executor)
            && ClassHelper::implements($executor[0] ?? '', ServiceCliExecutorInterface::class)
        ) {
            /** @var ServiceCliExecutorInterface $object */
            $object = new $executor[0]();
            $method = $executor[1] ?? '';
            if (method_exists($object, $method)) {
                $object->setProcessor($this);
                $object->{$method}();
            }
        }
	}

	private function loadCommandsExtensionList(): array
	{
	    $result = [];

        $servicesList = $this->getServicesList();
        foreach ($servicesList as $serviceName => $serviceData) {
            $service = $serviceData['object'];
            if (!$service) {
                continue;
            }

            $serviceCli = $service->cli;
            if (!$serviceCli instanceof ServiceCliInterface) {
                continue;
            }

            $result = array_merge($result, $serviceCli->getCliCommandsConfig());
        }

        return $result;
	}
}
