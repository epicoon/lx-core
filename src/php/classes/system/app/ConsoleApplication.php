<?php

namespace lx;

/**
 * Class ConsoleApplication
 * @package lx
 */
class ConsoleApplication extends AbstractApplication implements FusionInterface
{
	use FusionTrait;

	/** @var array */
	protected $argv;

    /**
     * @param array $argv
     */
	public function setArguments($argv)
    {
        $this->argv = $argv;
    }

	public function run()
	{
		if (empty($this->argv)) {
			return;
		}

		$command = array_pop($this->argv);
		switch ($command) {
			case 'cli':
				(new Cli())->run();
				break;


            case 'test':
                $service = $this->getService('lx/help');
                $service->runProcess('test');
                break;


			default:
				//TODO можно реализовать какие-то команды безоболочечные
				break;
		}
	}
}
