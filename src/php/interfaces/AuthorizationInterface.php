<?php

namespace lx;

interface AuthorizationInterface extends EventLestenerInterface
{
	public function checkUserHasRights($user, $rights);
	public function getManagePlugin();
}
