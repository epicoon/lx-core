<?php

namespace lx;

interface ResourceVoterInterface
{
	public function setResource(ResourceInterface $resource): void;

	public function processActionParams(
	    UserInterface $user,
        string $actionName,
        array $params
    ): array;

	public function run(
	    UserInterface $user,
        string $actionName,
        array $params = []
    ): bool;
}
