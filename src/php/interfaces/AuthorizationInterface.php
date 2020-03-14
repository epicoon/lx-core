<?php

namespace lx;

/**
 * This interface for authorization gate realisation
 *
 * Interface AuthorizationInterface
 * @package lx
 */
interface AuthorizationInterface extends EventListenerInterface
{
	/**
	 * @param User $user
	 * @param SourceAccessDataInterface $sourceAccessData
	 * @return mixed
	 */
	public function checkUserAccess($user, $sourceAccessData);

	/**
	 * @return Plugin
	 */
	public function getManagePlugin();
}
