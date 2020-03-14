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
	 * ConsoleApplication constructor.
	 * @param array $argv
	 */
	public function __construct($argv = [])
	{
		parent::__construct();

		$this->argv = $argv;

		$this->initFusionComponents([], [
			'events' => EventManager::class,
			'diProcessor' => DependencyProcessor::class,
		]);
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

			default:
				//TODO можно реализовать какие-то команды безоболочечные
				break;
		}
	}
}
