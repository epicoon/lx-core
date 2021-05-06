<?php

namespace lx;

interface AuthenticationInterface extends EventListenerInterface
{
	public function authenticateUser(?array $authData = null): ?UserInterface;
	/**
	 * Returns ResourceContext with plugin to get attempt authenticate user from front-end
	 */
	public function responseToAuthenticate(): ?ResourceContext;
	public function getProblemCode(): int;
}
