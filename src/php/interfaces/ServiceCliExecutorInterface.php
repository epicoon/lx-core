<?php

namespace lx;

interface ServiceCliExecutorInterface
{
	public function setProcessor(CliProcessor $processor): void;
    public function setCommand(CommandInterface $command): void;
	public function run(): void;
}
