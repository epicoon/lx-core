<?php

namespace lx;

/**
 * This interface for authentication gate realisation
 *
 * Interface AuthenticationInterface
 * @package lx
 */
interface AuthenticationInterface extends EventListenerInterface
{
	/**
	 * Try to authenticate user
	 * 
	 * @return bool
	 */
	public function authenticateUser();

	/**
	 * Returns SourceContext with plugin to get attempt authenticate user from front-end
	 *
	 * @return SourceContext|false
	 */
	public function responseToAuthenticate();

    /**
     * @return int
     */
    public function getProblemCode();
}
