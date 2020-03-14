<?php

namespace lx;

/**
 * Interface ServiceCliExecutorInterface
 * @package lx
 */
interface ServiceCliExecutorInterface
{
	/**
	 * @param CliProcessor $processor
	 */
	public function setProcessor($processor);

	/**
	 * @return void
	 */
	public function run();
}
