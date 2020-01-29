<?php

namespace lx;

interface UserProcessorInterface {
	public function setApplicationUser($authValue);
	public function getUserData($condition);
	public function getUser($condition);
	public function getUsersData($offset = 0, $limit = null);
	public function getUsers($offset = 0, $limit = null);
	public function findUserByPassword($login, $password);
	public function createUser($authValue, $password);
	public function deleteUser($authValue);
}
