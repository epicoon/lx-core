<?php

namespace lx;

/**
 * This interface for authorization gate implementation
 *
 * Interface AuthorizationInterface
 * @package lx
 */
interface AuthorizationInterface extends EventListenerInterface
{
	/**
	 * @param UserInterface $user
	 * @param ResourceAccessDataInterface $resourceAccessData
	 * @return mixed
	 */
	public function checkUserAccess($user, $resourceAccessData);

	/**
	 * @return Plugin
	 */
	public function getManagePlugin();
}
