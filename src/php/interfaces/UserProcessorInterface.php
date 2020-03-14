<?php

namespace lx;

/**
 * Interface UserProcessorInterface
 * @package lx
 */
interface UserProcessorInterface
{
	/**
	 * @param string $authValue
	 * @return bool
	 */
	public function setApplicationUser($authValue);

	/**
	 * @param string|array $condition
	 * @return ModelInterface
	 */
	public function getUser($condition);

	/**
	 * @param int $offset
	 * @param int $limit
	 * @return ModelInterface[]
	 */
	public function getUsers($offset = 0, $limit = null);

	/**
	 * @param string $authValue
	 * @param string $password
	 * @return ModelInterface
	 */
	public function findUserByPassword($authValue, $password);

	/**
	 * @param string $authValue
	 * @param string $password
	 * @return ModelInterface
	 */
	public function createUser($authValue, $password);

	/**
	 * @param string $authValue
	 */
	public function deleteUser($authValue);
}
