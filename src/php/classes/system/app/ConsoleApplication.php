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
	}
	
	private function processCli(): void
    {
        try {
            if (empty($this->args)) {
                (new Cli())->run();
            } else {
                (new CliProcessor())->handleCommand(
                    $this->command,
                    CliProcessor::COMMAND_TYPE_CONSOLE,
                    $this->args,
                    null, null
                );
            }
        } catch (\Throwable $exception) {
            \lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
                '__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
                'msg' => $exception->getMessage(),
            ]);
            echo $exception->getMessage() . PHP_EOL;
            echo 'Look for details in the dev log' . PHP_EOL;
        }
    }
}
