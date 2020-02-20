<?php

namespace lx;

interface AuthenticationInterface extends EventLestenerInterface {
	public function authenticateUser();
	public function responseToAuthenticate();
}
