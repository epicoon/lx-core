<?php

namespace lx;

/**
 * Interface UserProcessorInterface
 * @package lx
 */
interface UserProcessorInterface
{
    /**
     * @param mixed $authValue
     * @return ModelInterface|null
     */
    public function getUserModel($authValue);

	/**
	 * @param string $authValue
	 * @return bool
	 */
	public function setApplicationUser($authValue);

    /**
     * @return array
     */
	public function getPublicData();

	/**
	 * @param string|array $condition
	 * @return UserInterface
	 */
	public function getUser($condition);

	/**
	 * @param int $offset
	 * @param int $limit
	 * @return UserInterface[]
	 */
	public function getUsers($offset = 0, $limit = null);

	/**
	 * @param string $authValue
	 * @param string $password
	 * @return UserInterface
	 */
	public function findUserByPassword($authValue, $password);

	/**
	 * @param string $authValue
	 * @param string $password
	 * @return UserInterface
	 */
	public function createUser($authValue, $password);

	/**
	 * @param string $authValue
	 */
	public function deleteUser($authValue);
}
