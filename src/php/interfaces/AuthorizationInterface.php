<?php

namespace lx;

interface AuthorizationInterface extends EventListenerInterface
{
	/**
	 * @return mixed
	 */
	public function checkUserAccess(UserInterface $user, ResourceAccessDataInterface $resourceAccessData);
	public function getManagePlugin(): Plugin;
}
