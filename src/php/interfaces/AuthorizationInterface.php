<?php

namespace lx;

interface AuthorizationInterface extends EventLestenerInterface {
	public function checkAccess($user, $responseSource);
	public function getManagePlugin();
}
