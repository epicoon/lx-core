<?php

namespace lx;

/**
 * Interface ResourceVoterInterface
 * @package lx
 */
interface ResourceVoterInterface
{
	public function setResource(
	    Resource $resource
    );

	public function processActionParams(
	    UserInterface $user,
        string $actionName,
        array $params
    ): array;

	public function run(
	    UserInterface $user,
        string $actionName,
        array $params
    ): bool;
}
