<?php

namespace lx;

abstract class AbstractResourceVoter implements ResourceVoterInterface
{
	protected Resource $owner;

	public function getResource(): Resource
	{
		return $this->owner;
	}

	public function setResource(ResourceInterface $resource): void
	{
		$this->owner = $resource;
	}

	public function processActionParams(UserInterface $user, string $actionName, array $params): array
	{
		return $params;
	}

	abstract public function run(UserInterface $user, string $actionName, array $params): bool;
}
