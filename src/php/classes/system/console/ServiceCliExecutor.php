<?php

namespace lx;

/**
 * Class ServiceCliExecutor
 * @package lx
 */
class ServiceCliExecutor implements ServiceCliExecutorInterface
{
	/** @var CliProcessor */
	protected $processor;

	/**
	 * @param $processor CliProcessor
	 */
	public function setProcessor($processor)
	{
		$this->processor = $processor;
	}

	/**
	 * @return void
	 */
	public function run()
	{
		$this->processor->done();
	}
}
