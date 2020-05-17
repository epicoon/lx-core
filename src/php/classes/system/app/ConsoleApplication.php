<?php

namespace lx;

/**
 * Class ConsoleApplication
 * @package lx
 */
class ConsoleApplication extends AbstractApplication
{
    /** @var string */
    protected $command;

	/** @var array */
	protected $args;

    /**
     * @param array $argv
     */
	public function setArguments($argv)
    {
        $lx = array_shift($argv);
        $this->command = array_shift($argv);
        $this->args = $argv;
    }

	public function run()
	{
		if (!isset($this->command)) {
			return;
		}

		switch ($this->command) {
			case 'cli':
				(new Cli())->run();
				break;

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

                $service->runProcess($processName);
                echo 'Process has run' . PHP_EOL;
                break;

			default:
				//TODO можно реализовать какие-то команды безоболочечные
				break;
		}
	}
}
