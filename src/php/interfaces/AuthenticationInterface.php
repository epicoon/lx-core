<?php

namespace lx;

interface AuthenticationInterface {
	public function authenticateUser();
	public function responseToAuthenticate($responseSource);
	public function getJs();
}
