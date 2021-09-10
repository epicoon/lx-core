<?php

namespace lx;

interface AuthorizationInterface extends EventListenerInterface
{
	public function checkUserAccess(UserInterface $user, ResourceAccessDataInterface $resourceAccessData): bool;
}
