<?php

namespace lx;

interface AuthorizationInterface {
	public function checkAccess($user, $responseSource);
	public function getManageModule();
}
