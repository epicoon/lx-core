<?php

namespace lx;

/**
 * This interface for authentication gate implementation
 *
 * Interface AuthenticationInterface
 * @package lx
 */
interface AuthenticationInterface extends EventListenerInterface
{
	public function authenticateUser(?array $authData = null): ?UserInterface;

	/**
	 * Returns ResourceContext with plugin to get attempt authenticate user from front-end
	 *
	 * @return ResourceContext|false
	 */
	public function responseToAuthenticate();

    /**
     * @return int
     */
    public function getProblemCode();
}
