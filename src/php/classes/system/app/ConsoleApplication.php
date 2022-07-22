<?php

namespace lx;

/**
 * @property-read ConsoleRouter $router
 */
class ConsoleApplication extends AbstractApplication
{
    protected string $command = '';
	protected array $args = [];

    public function getDefaultFusionComponents(): array
    {
        return array_merge(parent::getDefaultFusionComponents(), [
            'router' => ConsoleRouter::class,
        ]);
    }

	public function setArguments(array $argv): void
    {
        array_shift($argv);
        $argsStr = implode(' ', $argv);
        list($this->command, $this->args) = CliProcessor::parseInput($argsStr);
    }

	public function run(): void
	{
		if (!isset($this->command)) {
            echo 'Command is undefined' . PHP_EOL;
			return;
		}

		if ($this->command == 'cli') {
		    $this->processCli();
		    return;
        }
        
        /** @var ConsoleResourceContext $resourceContext */
        $resourceContext = $this->router->route($this->command);
        if (!$resourceContext->validate()) {
            echo $resourceContext->getFirstFlightRecord() . PHP_EOL;
            return;
        }
        
        $resourceContext->setParams($this->args);
        $resourceContext->invoke();

        $e = 1;


        $showCommandInConsole = $this->args['w'] ?? false;
        $showCommandInFile = $this->args['f'] ?? false;
        if ($showCommandInConsole || $showCommandInFile) {
            $this->setParam('showCommand', true);
        }

		switch ($this->command) {
            case 'run':
                $serviceName = $this->args[0] ?? '';
                $service = $this->getService($serviceName);
                if (!$service) {
                    echo 'Service doesn\'t exist' . PHP_EOL;
                    return;
                }

                $processName = $this->args[1] ?? '';
                if (!$service->hasProcess($processName)) {
                    echo 'Process doesn\'t exist' . PHP_EOL;
                    return;
                }

                echo 'Process is running' . PHP_EOL;
                $result = $service->runProcess($processName);
                if ($result === null) {
                    echo 'Done' . PHP_EOL;
                } else {
                    echo $result . PHP_EOL;
                }
                break;

			default:
				//TODO можно реализовать какие-то команды безоболочечные
				break;
		}
        
        if ($showCommandInConsole) {
            echo $this->getParam('showCommand') . PHP_EOL;
        }
        if ($showCommandInFile) {
            $this->log($this->getParam('showCommand'), 'command-run');
        }
	}
	
	private function processCli(): void
    {
        if (empty($this->args)) {
            (new Cli())->run();
        } else {
            $processor = new CliProcessor();
            $argsStr = implode(' ', $this->args);
            list($command, $args) = $processor->parseInput($argsStr);
            $result = $processor->handleCommand(
                $command,
                CliProcessor::COMMAND_TYPE_CONSOLE,
                $args,
                null, null
            );
            //TODO допилить логирование ошибок и настройку вывода в консоль если надо
        }
    }
}
